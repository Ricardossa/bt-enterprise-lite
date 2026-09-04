<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;
use PDO;
use Throwable;

class QueueService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function emitir(string $prefixo, int $servicoId, string $clienteUuid, ?string $deviceId = null, string $tipo = 'NORMAL', int $operadorId = 0, float $valorTotal = 0, array $adicionais = [], string $nomeCliente = '', int $clienteId = 0): array
    {
        try {
            Database::begin();

            $agora = date('Y-m-d H:i:s');
            $hoje = date('Y-m-d');

            // [LITE SaaS] BUSCA PREFIXO E ÚLTIMO NÚMERO DO PROFISSIONAL (Isolado por Tenant)
            $tenantId = Auth::tenantId();
            $opInfo = Database::fetch("SELECT prefixo FROM operadores WHERE id = ? AND tenant_id = ?", [$operadorId, $tenantId]);
            $prefixoFinal = strtoupper($opInfo['prefixo'] ?? $prefixo);

            // [LITE v4.2.1] FONTE DA VERDADE: Calcula preÃ§o vigente direto no Core (Ignora input se promo ativa)
            $calc = ServicoService::getPrecoVigente($servicoId, $tenantId);
            $valorTotal = $calc['preco'];
            $isPromo = $calc['is_promo'];

            // Se houver servicos adicionais (Combos no Totem), recalcula o total
            if (!empty($adicionais)) {
                $valorTotal = 0; // Reseta para somar todos os itens do combo
                foreach ($adicionais as $sid) {
                    $itemCalc = ServicoService::getPrecoVigente((int)$sid, $tenantId);
                    $valorTotal += $itemCalc['preco'];
                    if ($itemCalc['is_promo']) $isPromo = true;
                }
            }

            $ultima = Database::fetch(
                "SELECT numero FROM senhas
                 WHERE operador_id = ? AND tenant_id = ?
                 AND DATE(created_at) = ?
                 ORDER BY numero DESC LIMIT 1",
                [$operadorId, $tenantId, $hoje]
            );

            $numero = $ultima ? ((int)$ultima['numero']) + 1 : 1;
            $sufixoTipo = ($tipo === 'PRIORITARIO') ? 'P' : '';
            $codigoGerado = $prefixoFinal . $sufixoTipo . str_pad((string)$numero, 3, '0', STR_PAD_LEFT);
            $uuid = bin2hex(random_bytes(16));

            // [LITE v1.5.0] Monta descricao dos servicos adicionais
            $desc = "";
            if (!empty($adicionais)) {
                $placeholders = implode(',', array_fill(0, count($adicionais), '?'));
                $params = array_merge($adicionais, [$tenantId]);
                $nomes = Database::fetchAll("SELECT nome FROM servicos WHERE id IN ($placeholders) AND tenant_id = ?", $params);
                $desc = implode(' + ', array_column($nomes, 'nome'));
            }

            // [LITE v2.8.0] Inteligência de Assinatura no Totem
            $usouAssinatura = false;
            $assinaturaId = 0;
            if ($clienteId > 0) {
                $assinatura = Database::fetch("
                    SELECT a.id, p.servico_vinculado_id
                    FROM clube_assinaturas a
                    JOIN clube_planos p ON p.id = a.plano_id
                    WHERE a.cliente_id = ? AND a.tenant_id = ? AND a.status = 'ATIVA'
                    AND a.data_fim >= CURDATE() AND a.cortes_restantes > 0
                    ORDER BY a.id DESC LIMIT 1
                ", [$clienteId, $tenantId]);

                if ($assinatura && (int)$servicoId === (int)$assinatura['servico_vinculado_id']) {
                    $valorTotal = 0;
                    $usouAssinatura = true;
                    $assinaturaId = (int)$assinatura['id'];
                }
            }

            $data = [
                'tenant_id' => $tenantId,
                'uuid' => $uuid,
                'cliente_uuid' => $clienteUuid,
                'cliente_id' => $clienteId,
                'servico_id' => $servicoId,
                'operador_id' => $operadorId,
                'numero' => $numero,
                'prefixo' => $prefixo,
                'codigo' => $codigoGerado,
                'nome_cliente' => $nomeCliente,
                'tipo_atendimento' => $tipo,
                'status' => 'AGUARDANDO',
                'device_id' => $deviceId,
                'valor_total' => $valorTotal,
                'is_promo' => $isPromo ? 1 : 0, // [LITE v4.2.0] Rastreio de promoção aplicada
                'pagamento_status' => $usouAssinatura ? 'ISENTO' : 'PENDENTE',
                'servicos_desc' => $desc,
                'created_at' => $agora,
                'emitida_em' => $agora
            ];

            $fields = implode(", ", array_keys($data));
            $placeholders = implode(", ", array_fill(0, count($data), "?"));

            Database::execute(
                "INSERT INTO senhas ($fields) VALUES ($placeholders)",
                array_values($data)
            );

            $id = Database::lastInsertId();

            // Se usou assinatura, debita o corte agora
            if ($usouAssinatura && $assinaturaId > 0) {
                Database::execute("UPDATE clube_assinaturas SET cortes_restantes = cortes_restantes - 1 WHERE id = ?", [$assinaturaId]);
            }

            // [LITE v3.5.9] Registra Atividade em Tempo Real
            ActivityService::log('SUCCESS', 'FILA', "Nova senha emitida: $codigoGerado", [], $nomeCliente ?: 'Totem');

            $pixData = null;

            // [LITE v2.5.2] GERAÇÃO DE PIX AUTOMÁTICA (MERCADO PAGO)
            if ($valorTotal > 0) {
                try {
                    $payment = new \BTQueue\Core\PaymentService();
                    $emailPayer = "cli_" . $clienteUuid . "@brandaotech.com";
                    $pixDesc = $desc ?: "Atendimento Barbeiro";

                    $pix = $payment->createPixPayment($valorTotal, $pixDesc, $emailPayer, (string)$id);

                    if ($pix['success']) {
                        $cleanB64 = preg_replace('/\s+/', '', (string)$pix['qr_code_base64']);
                        Database::execute(
                            "UPDATE senhas SET pagamento_id = ?, pix_qr_code = ?, pix_qr_base64 = ?, pagamento_status = 'PENDENTE' WHERE id = ? AND tenant_id = ?",
                            [$pix['id'], $pix['qr_code'], $cleanB64, $id, $tenantId]
                        );
                        $pixData = $pix;
                        $pixData['qr_code_base64'] = $cleanB64;
                    }
                } catch (Throwable $pe) {}
            }

            Database::commit();

            return [
                'success' => true,
                'id'      => $id,
                'senha'   => $codigoGerado,
                'uuid'    => $uuid,
                'pix'     => $pixData
            ];

        } catch (Throwable $e) {
            Database::rollback();
            throw new Exception($e->getMessage());
        }
    }

    public function chamar($param1, ?int $guicheId = null, ?string $atendente = null, ?int $forceOperadorId = null): array
    {
        try {
            Database::beginImmediate();
            $tenantId = Auth::tenantId();

            $isModoNovo = ($guicheId !== null);
            $guicheIdFinal = null;
            $servicoId = null;
            $operadorId = $forceOperadorId ?? 0;

            if ($isModoNovo) {
                $servicoId = (int)$param1;
                $guicheIdFinal = $guicheId;

                // Se não foi forçado, busca pelo guichê (Modo Legado/Totem)
                if ($operadorId <= 0) {
                    $opData = Database::fetch("SELECT id FROM operadores WHERE guiche_id = ? AND tenant_id = ? AND ativo = 1 LIMIT 1", [$guicheIdFinal, $tenantId]);
                    $operadorId = $opData ? (int)$opData['id'] : 0;
                }
            } else {
                $guicheCodigoLogico = (string)$param1;
                $guicheInfo = Database::fetch("SELECT id FROM guiches WHERE codigo = ? AND tenant_id = ? LIMIT 1", [$guicheCodigoLogico, $tenantId]);
                if (!$guicheInfo) {
                    Database::rollback();
                    return ['success' => false, 'message' => 'Guichê não encontrado.'];
                }
                $guicheIdFinal = (int)$guicheInfo['id'];
                $opData = Database::fetch("SELECT id FROM operadores WHERE guiche_id = ? AND tenant_id = ? AND ativo = 1 LIMIT 1", [$guicheIdFinal, $tenantId]);
                $operadorId = $opData ? (int)$opData['id'] : 0;
            }

            Database::execute(
                "UPDATE senhas SET status = 'FINALIZADA', finalizada_em = NOW()
                 WHERE status = 'CHAMANDO' AND guiche_id = ? AND tenant_id = ?",
                [$guicheIdFinal, $tenantId]
            );

            $cfgRows = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE chave IN ('priority_mode', 'priority_ratio', 'booking_release_mode') AND tenant_id = ?", [$tenantId]);
            $config = ['priority_mode' => 'STRICT', 'priority_ratio' => 3, 'booking_release_mode' => 'immediate'];
            foreach ($cfgRows as $r) { $config[$r['chave']] = $r['valor']; }

            $sqlBase = "SELECT s.* FROM senhas s
                        WHERE s.status IN ('AGUARDANDO', 'PRESENTE')
                        AND s.tenant_id = $tenantId
                        AND (DATE(s.created_at) = CURDATE() OR DATE(s.data_agendamento) = CURDATE())
                        AND (s.device_id IS NULL OR s.device_id = '' OR s.device_id NOT IN (
                            SELECT device_id FROM senhas WHERE status = 'CHAMANDO' AND tenant_id = $tenantId AND device_id IS NOT NULL AND device_id != ''
                        ))";

            // [LITE v3.3.6] Regra de Liberação após Pagamento
            if (($config['booking_release_mode'] ?? 'immediate') === 'after_payment') {
                $sqlBase .= " AND (s.pagamento_status IN ('PAGO', 'ISENTO')) ";
            }

            if ($operadorId > 0) {
                // [LITE v3.5.7] REGRA DE OURO: Operador chama APENAS suas senhas OU senhas da Fila Geral (ID 0)
                $especialidades = Database::fetchAll("SELECT servico_id FROM operador_servicos WHERE operador_id = ?", [$operadorId]);
                $servicoIds = array_column($especialidades, 'servico_id');

                $filter = "(s.operador_id = $operadorId"; // Senhas diretas para ele
                if (!empty($servicoIds)) {
                    $idsList = implode(',', $servicoIds);
                    // OU Senhas sem operador (0) mas que ele atende o serviço
                    $filter .= " OR ( (s.operador_id = 0 OR s.operador_id IS NULL) AND s.servico_id IN ($idsList) )";
                }
                $filter .= ")";
                $sqlBase .= " AND $filter";
            } elseif ($servicoId > 0) {
                $sqlBase .= " AND s.servico_id = $servicoId";
            }

            $sqlBase .= " ORDER BY
                CASE
                    -- P1: Agendados PRESENTES dentro da janela de 15 min ou já atrasados
                    WHEN s.status = 'PRESENTE' AND s.data_agendamento <= DATE_ADD(NOW(), INTERVAL 15 MINUTE) THEN 1
                    -- P2: Prioridades Locais (Idosos, etc)
                    WHEN s.tipo_atendimento = 'PRIORITARIO' THEN 2
                    -- P3: Restante (Normais e Agendados que chegaram cedo demais)
                    ELSE 3
                END ASC,
                -- Critério de desempate: Quem marcou mais cedo (para agendados) ou quem chegou primeiro (para normais)
                CASE WHEN s.status = 'PRESENTE' THEN s.data_agendamento ELSE s.created_at END ASC,
                s.id ASC LIMIT 1";
            $senha = Database::fetch($sqlBase);

            if (!$senha) {
                Database::rollback();
                return ['success' => false, 'message' => 'Nenhuma senha elegível.'];
            }

            $agora = date('Y-m-d H:i:s');

            // [LITE v3.5.8] VINCULA OPERADOR SE A SENHA FOR DA FILA GERAL (operador_id = 0)
            $paramsUpdate = [$guicheIdFinal, $atendente, $agora];
            $updateOpSql = "";
            if ((int)$senha['operador_id'] === 0 && $operadorId > 0) {
                $updateOpSql = ", operador_id = ?";
                $paramsUpdate[] = $operadorId;
            }
            $paramsUpdate[] = $senha['id'];
            $paramsUpdate[] = $tenantId;

            Database::execute(
                "UPDATE senhas SET status='CHAMANDO', guiche_id=?, atendente=?, chamada_em=? $updateOpSql WHERE id=? AND tenant_id = ?",
                $paramsUpdate
            );

            // [FIX v7.5.5] Inteligência de Nome: Prioriza Nome do Cliente para Agendados ou se existir
            $textoPainel = $senha['codigo'];
            if (!empty($senha['nome_cliente'])) {
                $isAgendado = (str_starts_with($senha['codigo'], 'AGD') || $senha['tipo_atendimento'] === 'AGENDAMENTO' || !empty($senha['data_agendamento']));

                // Se for agendado OU tiver nome curto (balcão identificado), usa o Nome
                if ($isAgendado || strlen($senha['codigo']) <= 3) {
                    $textoPainel = strtoupper($senha['nome_cliente']);
                }
            }

            // [NOVO] Busca o nome do guichê para que a TV possa falar o local corretamente
            $guicheRow = Database::fetch("SELECT nome FROM guiches WHERE id = ? AND tenant_id = ?", [$guicheIdFinal, $tenantId]);
            $guicheNomeFinal = $guicheRow ? $guicheRow['nome'] : "Guichê " . $guicheIdFinal;

            Database::execute(
                "INSERT INTO sync_queue (tenant_id, evento, entidade, referencia_id, payload, sincronizado) VALUES (?, ?, ?, ?, ?, 0)",
                [$tenantId, 'CHAMAR', 'senha', (int)$senha['id'], json_encode([
                    'senha' => $textoPainel,
                    'codigo' => $senha['codigo'],
                    'nome_cliente' => $senha['nome_cliente'],
                    'guiche' => $guicheIdFinal,
                    'guiche_nome' => $guicheNomeFinal // <--- CAMPO CRÍTICO PARA O ÁUDIO
                ], JSON_UNESCAPED_UNICODE)]
            );

            // [LITE v3.5.9] Registra Atividade em Tempo Real
            ActivityService::log('INFO', 'FILA', "Senha {$senha['codigo']} chamada para $guicheNomeFinal", [], $atendente);

            Database::commit();

            return [
                'success' => true,
                'id' => $senha['id'],
                'codigo' => $senha['codigo'],
                'guiche' => $guicheIdFinal
            ];

        } catch (Throwable $e) {
            Database::rollback();
            throw new Exception($e->getMessage());
        }
    }

    public function chamarEspecifico(int $id, int $guicheId, ?string $atendente = null): array
    {
        try {
            Database::beginImmediate();
            $tenantId = Auth::tenantId();

            // No LITE, os agendamentos online tem status PRESENTE depois do check-in e status AGENDADO antes
            $senha = Database::fetch("SELECT * FROM senhas WHERE id = ? AND tenant_id = ? AND status IN ('AGUARDANDO', 'CONGELADA', 'AGENDADO', 'PRESENTE') LIMIT 1", [$id, $tenantId]);
            if (!$senha) {
                Database::rollback();
                return ['success' => false, 'message' => 'Senha não encontrada.'];
            }

            $agora = date('Y-m-d H:i:s');

            // [LITE v3.5.8] Vincula operador se for senha da Fila Geral
            $opData = Database::fetch("SELECT id FROM operadores WHERE guiche_id = ? AND tenant_id = ? AND ativo = 1 LIMIT 1", [$guicheId, $tenantId]);
            $operadorId = $opData ? (int)$opData['id'] : 0;

            $paramsUpdate = [$guicheId, $atendente, $agora];
            $updateOpSql = "";
            if ((int)$senha['operador_id'] === 0 && $operadorId > 0) {
                $updateOpSql = ", operador_id = ?";
                $paramsUpdate[] = $operadorId;
            }
            $paramsUpdate[] = $id;
            $paramsUpdate[] = $tenantId;

            Database::execute(
                "UPDATE senhas SET status='CHAMANDO', guiche_id=?, atendente=?, chamada_em=? $updateOpSql WHERE id=? AND tenant_id = ?",
                $paramsUpdate
            );

            // [FIX LITE]: Adiciona na Fila de Sincronização do Painel/TV o NOME se for agendado
            $textoPainel = $senha['codigo'];
            if (!empty($senha['nome_cliente']) && (
                strlen($senha['codigo']) <= 3 ||
                $senha['tipo_atendimento'] === 'AGENDAMENTO' ||
                !empty($senha['data_agendamento'])
            )) {
                $textoPainel = strtoupper($senha['nome_cliente']); // Nome completo
            }

            // [NOVO] Busca o nome do guichê para que a TV possa falar o local corretamente
            $guicheRow = Database::fetch("SELECT nome FROM guiches WHERE id = ? AND tenant_id = ?", [$guicheId, $tenantId]);
            $guicheNomeFinal = $guicheRow ? $guicheRow['nome'] : "Guichê " . $guicheId;

            Database::execute(
                "INSERT INTO sync_queue (tenant_id, evento, entidade, referencia_id, payload, sincronizado) VALUES (?, ?, ?, ?, ?, 0)",
                [$tenantId, 'CHAMAR', 'senha', (int)$id, json_encode([
                    'senha' => $textoPainel,
                    'codigo' => $senha['codigo'],
                    'nome_cliente' => $senha['nome_cliente'],
                    'guiche' => $guicheId,
                    'guiche_nome' => $guicheNomeFinal // <--- CAMPO CRÍTICO PARA O ÁUDIO
                ], JSON_UNESCAPED_UNICODE)]
            );

            Database::commit();
            return ['success' => true, 'id' => $id, 'codigo' => $senha['codigo']];
        } catch (Throwable $e) {
            Database::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function finalizar(int $id): array
    {
        try {
            Database::beginImmediate();
            $tenantId = Auth::tenantId();

            $senha = Database::fetch("SELECT device_id FROM senhas WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
            if (!$senha) throw new Exception("Senha não encontrada.");

            Database::execute("UPDATE senhas SET status='FINALIZADA', finalizada_em=NOW() WHERE id=? AND tenant_id = ?", [$id, $tenantId]);

            Database::commit();
            return ['success' => true];
        } catch (Throwable $e) {
            Database::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAgendados(?int $servicoId = null): array
    {
        try {
            $tenantId = Auth::tenantId();
            $hoje = date('Y-m-d');
            $sql = "SELECT id, codigo, nome_cliente, data_agendamento, status, servicos_desc, valor_total, pagamento_status
                    FROM senhas
                    WHERE tenant_id = ? AND status IN ('AGENDADO', 'PRESENTE')
                    AND date(data_agendamento) = ? ";

            $params = [$tenantId, $hoje];
            if ($servicoId) {
                $sql .= " AND servico_id = ? ";
                $params[] = $servicoId;
            }

            $sql .= " GROUP BY nome_cliente, data_agendamento ORDER BY data_agendamento ASC";
            return Database::fetchAll($sql, $params);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function estado($param1 = null, ?int $guicheId = null, ?int $forceOperadorId = null): array
    {
        $tenantId = Auth::tenantId();
        $servicoId = null;
        $guicheCodigo = '01';
        $chamando = null;
        $fila = [];
        $operadorId = $forceOperadorId;

        if ($guicheId !== null) {
            $servicoId = ($param1 !== null) ? (int)$param1 : null;
            $guicheInfo = Database::fetch("SELECT nome FROM guiches WHERE id = ? AND tenant_id = ? LIMIT 1", [$guicheId, $tenantId]);
            $guicheCodigo = $guicheInfo ? $guicheInfo['nome'] : "Cadeira " . $guicheId;

            if (!$operadorId) {
                $opData = Database::fetch("SELECT id FROM operadores WHERE guiche_id = ? AND tenant_id = ? AND ativo = 1 LIMIT 1", [$guicheId, $tenantId]);
                $operadorId = $opData ? (int)$opData['id'] : 0;
            }

            $chamando = Database::fetch(
                "SELECT s.*, sv.nome as servico_nome FROM senhas s
                 JOIN servicos sv ON s.servico_id = sv.id
                 WHERE s.status='CHAMANDO' AND s.guiche_id = ? AND s.tenant_id = ? ORDER BY s.chamada_em DESC LIMIT 1",
                [$guicheId, $tenantId]
            );
        } else {
            // [LITE v3.9.1] MODO MURAL GLOBAL: Busca a última chamada com JOIN no Guichê para voz Alexa
            $chamando = Database::fetch(
                "SELECT s.*, sv.nome as servico_nome, g.nome as guiche_nome
                 FROM senhas s
                 JOIN servicos sv ON s.servico_id = sv.id
                 LEFT JOIN guiches g ON g.id = s.guiche_id
                 WHERE s.status='CHAMANDO' AND s.tenant_id = ? ORDER BY s.chamada_em DESC LIMIT 1",
                [$tenantId]
            );
            if ($chamando) {
                $guicheCodigo = $chamando['guiche_nome'] ?: "Cadeira " . $chamando['guiche_id'];
            }
        }

        // [LITE v3.9.0] CARGA DE FILA E AGENDA (Agora fora do bloco de Guichê para suportar TV/Mural)
        $releaseMode = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'booking_release_mode' AND tenant_id = ? LIMIT 1", [$tenantId])['valor'] ?? 'immediate';

        $sqlFila = "SELECT s.*, sv.nome as servico_nome
                    FROM senhas s
                    LEFT JOIN servicos sv ON s.servico_id = sv.id
                    WHERE s.status IN ('AGUARDANDO', 'CONGELADA', 'PRESENTE')
                    AND s.tenant_id = ?
                    AND (DATE(s.created_at) = CURDATE() OR DATE(s.data_agendamento) = CURDATE()) ";

        if ($releaseMode === 'after_payment') {
            $sqlFila .= " AND (s.pagamento_status IN ('PAGO', 'ISENTO')) ";
        }

        if ($operadorId > 0) {
            $especialidades = Database::fetchAll("SELECT servico_id FROM operador_servicos WHERE operador_id = ?", [$operadorId]);
            $servicoIds = array_column($especialidades, 'servico_id');
            $filter = "(s.operador_id = $operadorId";
            if (!empty($servicoIds)) {
                $idsList = implode(',', $servicoIds);
                $filter .= " OR ( (s.operador_id = 0 OR s.operador_id IS NULL) AND s.servico_id IN ($idsList) )";
            }
            $filter .= ")";
            $sqlFila .= " AND $filter";
        }

        $sqlFila .= " ORDER BY s.id ASC";
        $fila = Database::fetchAll($sqlFila, [$tenantId]);

        $sqlAgd = "SELECT s.id, s.codigo, s.nome_cliente, s.data_agendamento, s.status, s.servicos_desc, s.valor_total, s.pagamento_status, o.nome as barbeiro_nome
                   FROM senhas s
                   LEFT JOIN operadores o ON o.id = s.operador_id
                   WHERE s.tenant_id = ? AND s.status IN ('AGENDADO', 'PRESENTE')
                   AND DATE(s.data_agendamento) = CURDATE() ";

        if ($operadorId > 0) $sqlAgd .= " AND operador_id = $operadorId";
        $sqlAgd .= " ORDER BY data_agendamento ASC";
        $agendados = Database::fetchAll($sqlAgd, [$tenantId]);

        $operadorNome = 'Operador Geral';
        $operadorStatus = 'ONLINE';
        if ($operadorId > 0) {
            $operador = Database::fetch("SELECT nome, status FROM operadores WHERE id = ? AND tenant_id = ? LIMIT 1", [$operadorId, $tenantId]);
            if ($operador) {
                $operadorNome = $operador['nome'];
                $operadorStatus = $operador['status'] ?: 'ONLINE';
            }
        }

        $config = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'label_cliente' AND tenant_id = ? LIMIT 1", [$tenantId]);
        $label = $config['valor'] ?? 'Paciente';

        $ganhos = 0; $totalServicos = 0;
        if ($operadorId > 0) {
            $fin = Database::fetch("SELECT SUM(valor_total) as total, COUNT(*) as qtd FROM senhas WHERE operador_id = ? AND tenant_id = ? AND status = 'FINALIZADA' AND DATE(finalizada_em) = CURDATE()
", [$operadorId, $tenantId]);
            $ganhos = (float)($fin['total'] ?? 0);
            $totalServicos = (int)($fin['qtd'] ?? 0);
        }

        return [
            'operador_nome' => $operadorNome,
            'operador_id' => $operadorId,
            'status' => $operadorStatus,
            'guiche_codigo' => $guicheCodigo,
            'label_cliente' => $label,
            'ganhos_hoje' => $ganhos,
            'servicos_hoje' => $totalServicos,
            'chamando' => $chamando ? [
                'id' => $chamando['id'],
                'uuid' => $chamando['uuid'],
                'codigo' => $chamando['codigo'],
                'senha' => !empty($chamando['nome_cliente']) ? $chamando['nome_cliente'] : $chamando['codigo'],
                'nome_cliente' => $chamando['nome_cliente'],
                'is_hospital' => !empty($chamando['nome_cliente']),
                'servico_nome' => $chamando['servicos_desc'] ?: $chamando['servico_nome'],
                'guiche' => $guicheCodigo,
                'barbeiro' => $chamando['atendente'] ?: 'Equipe',
                'pagamento_status' => $chamando['pagamento_status']
            ] : null,
            'fila' => array_map(fn($i) => [
                'id' => (int)$i['id'],
                'codigo' => $i['codigo'],
                'nome_cliente' => $i['nome_cliente'],
                'servico_nome' => $i['servicos_desc'] ?: $i['servico_nome'],
                'status' => $i['status'],
                'valor_total' => (float)$i['valor_total'],
                'pagamento_status' => $i['pagamento_status'] // [v3.9.4] Incluído para detecção VIP
            ], $fila ?? []),
            'agendados' => array_map(fn($i) => ['id'=>(int)$i['id'], 'codigo'=>$i['codigo'], 'nome_cliente'=>$i['nome_cliente'], 'data_agendamento'=>$i['data_agendamento'], 'status'=>$i['status'], 'barbeiro_nome'=>$i['barbeiro_nome']], $agendados ?? []),
            'historico' => $this->getHistoricoChamadas(5) // [v1.8.1] Injetando histórico no estado
        ];
    }

    public function getHistoricoChamadas(int $limit = 5): array
    {
        $tenantId = Auth::tenantId();
        return Database::fetchAll("
            SELECT s.codigo as senha, s.nome_cliente, g.nome as guiche_nome, s.chamada_em, s.pagamento_status
            FROM senhas s
            LEFT JOIN guiches g ON g.id = s.guiche_id
            WHERE s.status IN ('CHAMANDO', 'FINALIZADA')
            AND s.tenant_id = ?
            AND DATE(s.chamada_em) = CURDATE()
            ORDER BY s.chamada_em DESC LIMIT ?",
        [$tenantId, $limit]);
    }

    public function getStatsPorPeriodo(?string $inicio = null, ?string $fim = null): array
    {
        $tenantId = Auth::tenantId();

        if ($tenantId <= 0) {
            return [
                'emitidas' => 0,
                'chamadas' => 0,
                'pendentes' => 0,
                'finalizadas' => 0,
                'success' => false
            ];
        }

        $pInicio = $inicio ?? date('Y-m-d');
        $pFim = $fim ?? date('Y-m-d');

        $row = Database::fetch(
            "SELECT
                SUM(CASE WHEN DATE(created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) AS emitidas,
                SUM(CASE WHEN status IN ('CHAMANDO', 'FINALIZADA') AND DATE(chamada_em) BETWEEN ? AND ? THEN 1 ELSE 0 END) AS chamadas,
                SUM(CASE WHEN status IN ('AGUARDANDO', 'PRESENTE') AND (DATE(created_at) BETWEEN ? AND ? OR DATE(data_agendamento) BETWEEN ? AND ?) THEN 1 ELSE 0 END) AS pendentes,
                SUM(CASE WHEN status = 'FINALIZADA' AND DATE(finalizada_em) BETWEEN ? AND ? THEN 1 ELSE 0 END) AS finalizadas,
                SUM(CASE WHEN status = 'FINALIZADA' AND DATE(finalizada_em) BETWEEN ? AND ? THEN valor_total ELSE 0 END) AS ganhos_hoje
             FROM senhas
             WHERE tenant_id = ?
             AND (
                DATE(created_at) BETWEEN ? AND ?
                OR DATE(finalizada_em) BETWEEN ? AND ?
                OR DATE(data_agendamento) BETWEEN ? AND ?
             )",
            [$pInicio, $pFim, $pInicio, $pFim, $pInicio, $pFim, $pInicio, $pFim, $pInicio, $pFim, $pInicio, $pFim, $tenantId, $pInicio, $pFim, $pInicio, $pFim, $pInicio, $pFim]
        );

        return [
            'emitidas' => (int)($row['emitidas'] ?? 0),
            'chamadas' => (int)($row['chamadas'] ?? 0),
            'pendentes' => (int)($row['pendentes'] ?? 0),
            'finalizadas' => (int)($row['finalizadas'] ?? 0),
            'ganhos_hoje' => (float)($row['ganhos_hoje'] ?? 0),
            'success' => true
        ];
    }

    public function estatisticas(): array { return $this->getStatsPorPeriodo(); }
}
