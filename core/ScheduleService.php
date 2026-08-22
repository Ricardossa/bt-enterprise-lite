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

        // 3. Gera a grade de horários
        $slots = [];
        $atual = strtotime("$data $inicio");
        $limite = strtotime("$data $fim");

        // [LITE v3.3.9] Sincronia de Tempo com DB (Anti-Fuso)
        $dbNowRaw = Database::fetch("SELECT NOW() as agora, UNIX_TIMESTAMP(NOW()) as ts") ?: ['agora' => date('Y-m-d H:i:s'), 'ts' => time()];
        $dbNowTs = (int)$dbNowRaw['ts'];
        $dbHoje = date('Y-m-d', $dbNowTs);

        while ($atual < $limite) {
            $horaFormatada = date('H:i', $atual);

            // [LITE v3.5.2] Regra de Antecedência (Mínimo 30 minutos para agendar no mesmo dia)
            $minLeadTime = 1800; // 30 minutos em segundos
            $isMuitoPerto = ($dbHoje === $data && ($atual - $dbNowTs) < $minLeadTime);

            // Só adiciona se não estiver ocupado e respeitar a antecedência
            if (!in_array($horaFormatada, $ocupados) && !$isMuitoPerto) {
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

            // [LITE v2.6.4] VALOR TOTAL
            $valorTotal = 0; $nomesServicos = [];
            if (!empty($servicoIds)) {
                $placeholders = implode(',', array_fill(0, count($servicoIds), '?'));
                $params = array_merge($servicoIds, [$tenantId]);
                $servicosInfo = Database::fetchAll("SELECT nome, preco FROM servicos WHERE id IN ($placeholders) AND tenant_id = ?", $params);
                foreach ($servicosInfo as $si) { $valorTotal += (float)$si['preco']; $nomesServicos[] = $si['nome']; }
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
                    pagamento_status, valor_total, servicos_desc, operador_id
                ) VALUES (?, ?, ?, ?, ?, 0, 'G', ?, 'AGENDADO', ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)",
                [$tenantId, $uuid, ($deviceId ?: $uuid), $clienteId, $primeiroNome, $nome, $dataHora, $principalServicoId, $dataHora, $whatsapp, $cancelToken, ($valorTotal > 0 ? 'PENDENTE' : 'ISENTO'), $valorTotal, $servicosDesc, $operadorId]
            );

            $senhaId = Database::lastInsertId();

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
        $agendado = Database::fetch("SELECT id FROM senhas WHERE cancel_token = ? AND tenant_id = ? AND status = 'AGENDADO' LIMIT 1", [$token, $tenantId]);
        if (!$agendado) return ['success' => false, 'message' => 'Agendamento não encontrado.'];
        Database::execute("UPDATE senhas SET status = 'CANCELADO', updated_at = NOW() WHERE id = ? AND tenant_id = ?", [$agendado['id'], $tenantId]);
        return ['success' => true, 'message' => 'Agendamento cancelado.'];
    }
}
