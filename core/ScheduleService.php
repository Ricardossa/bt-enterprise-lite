<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;
use PDO;
use Throwable;

/**
 * Motor de Agendamento Nativo - BT Scheduler
 * Gerencia slots de tempo, disponibilidade e reservas.
 */
final class ScheduleService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Retorna os horários disponíveis para um profissional em uma data específica.
     */
    public function getSlotsDisponiveis(int $operadorId, string $data): array
    {
        $tenantId = Auth::tenantId();

        // [LITE v3.3.4] Verifica Status do Profissional (Blindado contra coluna ausente)
        try {
            $opInfo = Database::fetch("SELECT status FROM operadores WHERE id = ? AND tenant_id = ? LIMIT 1", [$operadorId, $tenantId]);
            if ($opInfo && $opInfo['status'] === 'BREAK' && $data === date('Y-m-d')) {
                return []; // Bloqueia agendamentos para hoje enquanto estiver em pausa
            }
        } catch (\Throwable $e) {
            // Se a coluna 'status' ainda não existir, ignora o bloqueio por pausa
        }

        // 1. Busca a regra de funcionamento para o dia da semana (Garante que esteja ativa)
        $diaSemana = (int)date('w', strtotime($data));

        // [LITE v2.6.3] Busca regra exclusivamente pelo OPERADOR e Tenant
        $regra = Database::fetch(
            "SELECT * FROM agenda_regras WHERE operador_id = ? AND tenant_id = ? AND dia_semana = ? AND ativo = 1 LIMIT 1",
            [$operadorId, $tenantId, $diaSemana]
        );

        if (!$regra) return [];

        // --- TRAVA DE JANELA DE LIBERAÇÃO (v6.1 Diamond) ---
        if ($regra['liberacao_dia_semana'] !== null) {
            $hojeDiaSemana = (int)date('w');
            $agoraHora = date('H:i');

            // Se hoje não for o dia de abertura OU estiver fora da janela de horário
            if ($hojeDiaSemana !== (int)$regra['liberacao_dia_semana'] ||
                ($agoraHora < $regra['liberacao_hora_inicio'] || $agoraHora > $regra['liberacao_hora_fim'])) {
                return []; // Esconde a data
            }
        }

        $inicio = $regra['hora_inicio'];
        $fim = $regra['hora_fim'];
        $duracao = (int)$regra['duracao_slot']; // em minutos

        // 2. Busca slots já ocupados no banco (v7.3.0: Agora inclui status PRESENTE)
        $ocupadosRaw = Database::fetchAll(
            "SELECT data_agendamento FROM senhas
             WHERE operador_id = ? AND tenant_id = ?
             AND DATE(data_agendamento) = ?
             AND status IN ('AGENDADO', 'CHAMANDO', 'FINALIZADA', 'PRESENTE')",
            [$operadorId, $tenantId, $data]
        );

        $ocupados = array_map(function($item) {
            return date('H:i', strtotime($item['data_agendamento']));
        }, $ocupadosRaw);

        // [v2.9.0] Busca Bloqueios Específicos (Compromissos)
        $bloqueios = Database::fetchAll(
            "SELECT hora_inicio, hora_fim FROM agenda_bloqueios
             WHERE tenant_id = ? AND data = ? AND (operador_id = ? OR operador_id = 0)",
            [$tenantId, $data, $operadorId]
        );

        // 3. Gera a grade de horários
        $slots = [];
        $atual = strtotime("$data $inicio");
        $limite = strtotime("$data $fim");

        // [LITE v3.3.9] Sincronia de Tempo com DB (Anti-Fuso)
        $dbNowRaw = Database::fetch("SELECT NOW() as agora, UNIX_TIMESTAMP(NOW()) as ts") ?: ['agora' => date('Y-m-d H:i:s'), 'ts' => time()];
        $dbNowTs = (int)$dbNowRaw['ts'];
        $dbHoje = date('Y-m-d', $dbNowTs);

        $pausaIni = $regra['pausa_inicio'] ? strtotime("$data {$regra['pausa_inicio']}") : null;
        $pausaFim = $regra['pausa_fim'] ? strtotime("$data {$regra['pausa_fim']}") : null;

        while ($atual < $limite) {
            $horaFormatada = date('H:i', $atual);

            // [LITE v3.5.2] Regra de Antecedência (Mínimo 30 minutos para agendar no mesmo dia)
            $minLeadTime = 1800; // 30 minutos em segundos
            $isMuitoPerto = ($dbHoje === $data && ($atual - $dbNowTs) < $minLeadTime);

            // [v2.9.0] Verifica se está dentro da PAUSA RECORRENTE (Almoço)
            $estaEmPausa = false;
            if ($pausaIni && $pausaFim && $atual >= $pausaIni && $atual < $pausaFim) {
                $estaEmPausa = true;
            }

            // [v2.9.0] Verifica se está dentro de um BLOQUEIO ESPECÍFICO
            $estaBloqueado = false;
            foreach ($bloqueios as $b) {
                $bIni = strtotime("$data {$b['hora_inicio']}");
                $bFim = strtotime("$data {$b['hora_fim']}");
                if ($atual >= $bIni && $atual < $bFim) {
                    $estaBloqueado = true;
                    break;
                }
            }

            // Só adiciona se não estiver ocupado, não for muito perto, não estiver em pausa e não estiver bloqueado
            if (!in_array($horaFormatada, $ocupados) && !$isMuitoPerto && !$estaEmPausa && !$estaBloqueado) {
                $slots[] = $horaFormatada;
            }

            $atual = strtotime("+$duracao minutes", $atual);
        }

        return $slots;
    }

    /**
     * Realiza a reserva de um slot com regras de Compliance Triplo (v6.4).
     */
    public function reservar(array|int $servicoIds, string $nome, string $dataHora, string $whatsapp = '', string $deviceId = '', int $operadorId = 0, int $clienteId = 0): array
    {
        if (is_int($servicoIds)) { $servicoIds = [$servicoIds]; }

        try {
            Database::beginImmediate();
            $tenantId = Auth::tenantId();

            $hojeData = date('Y-m-d', strtotime($dataHora));
            $horaMinuto = date('H:i', strtotime($dataHora));
            $principalServicoId = $servicoIds[0] ?? 0;

            // --- 0. VALIDAÇÃO DE GRADE ---
            $slotsValidos = $this->getSlotsDisponiveis($operadorId, $hojeData);
            if (!in_array($horaMinuto, $slotsValidos)) {
                throw new Exception("Desculpe, o horário selecionado não está mais disponível ou é inválido.");
            }

            // 1. VERIFICA SUSPENSÃO
            $whatsappLimpo = preg_replace('/\D/', '', $whatsapp);
            $sqlSusp = "SELECT id FROM agenda_suspensoes WHERE tenant_id = ? AND data_fim >= CURDATE() AND identificador = ? LIMIT 1";
            if (Database::fetch($sqlSusp, [$tenantId, $nome])) {
                throw new Exception("Seu acesso está suspenso por descumprimento das regras.");
            }

            // [LITE v3.5.1] REGRA DE DUPLICIDADE DESATIVADA POR SOLICITAÇÃO
            // Permitindo múltiplos agendamentos no mesmo dia para o mesmo cliente.

            // [LITE v2.6.4] VALOR TOTAL & INTELIGÊNCIA VIP (v4.5)
            $valorTotal = 0; $nomesServicos = [];

            // Busca Assinatura Ativa do Cliente
            $assinatura = null;
            if ($clienteId > 0) {
                $assinatura = Database::fetch("
                    SELECT a.*, p.servico_vinculado_id
                    FROM clube_assinaturas a
                    JOIN clube_planos p ON p.id = a.plano_id
                    WHERE a.cliente_id = ? AND a.tenant_id = ? AND a.status = 'ATIVA'
                    AND a.data_fim >= CURDATE() AND a.cortes_restantes > 0
                    ORDER BY a.id DESC LIMIT 1
                ", [$clienteId, $tenantId]);
            }

            $usouCombo = false;
            $isPromo = false;
            if (!empty($servicoIds)) {
                foreach ($servicoIds as $sid) {
                    $calc = ServicoService::getPrecoVigente((int)$sid, $tenantId, $hojeData);
                    $precoServico = $calc['preco'];

                    if ($calc['is_promo']) $isPromo = true;

                    // Busca informaÃ§Ãµes do nome para o descritivo
                    $sInfo = Database::fetch("SELECT nome FROM servicos WHERE id = ? AND tenant_id = ?", [(int)$sid, $tenantId]);
                    $nomeBase = $sInfo['nome'] ?? 'Serviço';

                    // Se o cliente tem combo e o serviço está vinculado ao plano dele
                    if ($assinatura && (int)$sid === (int)$assinatura['servico_vinculado_id'] && !$usouCombo) {
                        $precoServico = 0; // Não cobra este serviço (CORTESIA VIP)
                        $usouCombo = true;
                        $nomesServicos[] = $nomeBase . " (VIP)";
                    } else {
                        $valorTotal += $precoServico;
                        $nomesServicos[] = ($calc['is_promo'] ? $nomeBase . " (PROMO)" : $nomeBase);
                    }
                }
            }
            $servicosDesc = implode(' + ', $nomesServicos);

            $uuid = bin2hex(random_bytes(16));
            $cancelToken = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $numeroAgendamento = (int)Database::fetch("SELECT COUNT(*) + 1 AS total FROM senhas WHERE tenant_id = ? AND DATE(created_at) = CURDATE()", [$tenantId])['total'];
            $primeiroNome = 'AGD' . str_pad((string)$numeroAgendamento, 3, '0', STR_PAD_LEFT);

            Database::execute(
                "INSERT INTO senhas (
                    tenant_id, uuid, cliente_uuid, cliente_id, codigo, numero, prefixo,
                    nome_cliente, status, data_agendamento, servico_id, created_at, emitida_em, whatsapp, cancel_token,
                    pagamento_status, valor_total, is_promo, servicos_desc, operador_id, tipo_atendimento
                ) VALUES (?, ?, ?, ?, ?, 0, 'G', ?, 'AGENDADO', ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, 'AGENDAMENTO')",
                [$tenantId, $uuid, ($deviceId ?: $uuid), $clienteId, $primeiroNome, $nome, $dataHora, $principalServicoId, $dataHora, $whatsapp, $cancelToken, ($valorTotal > 0 ? 'PENDENTE' : 'ISENTO'), $valorTotal, ($isPromo ? 1 : 0), $servicosDesc, $operadorId]
            );

            $senhaId = Database::lastInsertId();

            // [v4.5] Se usou o combo, debita 1 crédito da assinatura
            if ($usouCombo && $assinatura) {
                Database::execute("UPDATE clube_assinaturas SET cortes_restantes = cortes_restantes - 1 WHERE id = ?", [$assinatura['id']]);
            }

            // GERAR PAGAMENTO
            $pixData = null;
            if ($valorTotal > 0) {
                $payment = new PaymentService();
                $pix = $payment->createPixPayment($valorTotal, "Reserva: " . $servicosDesc, $whatsapp . "@brandaotech.com", (string)$senhaId);
                if ($pix['success']) {
                    Database::execute("UPDATE senhas SET pagamento_id = ?, pix_qr_code = ?, pix_qr_base64 = ? WHERE id = ? AND tenant_id = ?", [$pix['id'], $pix['qr_code'], $pix['qr_code_base64'], $senhaId, $tenantId]);
                    $pixData = $pix;
                }
            }

            Database::commit();

            return [
                'success' => true,
                'id' => $senhaId,
                'uuid' => $uuid,
                'token' => $cancelToken,
                'horario' => date('H:i', strtotime($dataHora)),
                'data' => date('d/m/Y', strtotime($dataHora)),
                'whatsapp' => $whatsapp,
                'pix' => $pixData
            ];
        } catch (Exception $e) {
            Database::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAgendados(?int $servicoId = null): array
    {
        try {
            $tenantId = Auth::tenantId();
            $hoje = date('Y-m-d');
            $releaseMode = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'booking_release_mode' AND tenant_id = ? LIMIT 1", [$tenantId])['valor'] ?? 'immediate';

            $sql = "SELECT id, uuid, codigo, nome_cliente, data_agendamento, status, servicos_desc, valor_total, pagamento_status, whatsapp
                    FROM senhas
                    WHERE tenant_id = ? AND status IN ('AGENDADO', 'PRESENTE')
                    AND DATE(data_agendamento) = ? ";

            if ($releaseMode === 'after_payment') {
                $sql .= " AND (pagamento_status IN ('PAGO', 'ISENTO')) ";
            }

            $params = [$tenantId, $hoje];
            if ($servicoId) { $sql .= " AND servico_id = ? "; $params[] = $servicoId; }

            $sql .= " ORDER BY data_agendamento ASC";
            return Database::fetchAll($sql, $params);
        } catch (Throwable $e) { return []; }
    }

    public function cancelar(string $token): array
    {
        $tenantId = Auth::tenantId();

        // [v2.9.2] Blindagem de Cancelamento: Só permite se ainda NÃO deu entrada (status AGENDADO apenas)
        $agendado = Database::fetch("
            SELECT id, status FROM senhas
            WHERE cancel_token = ? AND tenant_id = ?
            LIMIT 1", [$token, $tenantId]);

        if (!$agendado) return ['success' => false, 'message' => 'Agendamento não encontrado.'];

        if ($agendado['status'] !== 'AGENDADO') {
            return [
                'success' => false,
                'message' => '⚠️ Este agendamento não pode mais ser cancelado pois você já confirmou sua presença ou já foi chamado.'
            ];
        }

        Database::execute("UPDATE senhas SET status = 'CANCELADO', updated_at = NOW() WHERE id = ? AND tenant_id = ?", [$agendado['id'], $tenantId]);
        return ['success' => true, 'message' => 'Agendamento cancelado com sucesso.'];
    }
}
