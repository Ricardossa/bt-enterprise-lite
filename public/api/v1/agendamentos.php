<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';

use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

/**
 * API DE AGENDAMENTO PARA CHATBOT (n8n/Typebot)
 * [v1.0.0] Integração MariaDB SaaS Diamond
 */

try {
    // 1. Identifica o Tenant (Barbearia) via Hostname ou Parâmetro
    // O Chatbot deve mandar o tenant_id ou o domínio no Header ou GET
    $tenantId = (int)($_GET['tenant_id'] ?? 0);

    if ($tenantId <= 0) {
        $tenant = Auth::getCurrentTenant();
        $tenantId = $tenant ? (int)$tenant['id'] : 0;
    }

    if ($tenantId <= 0) throw new Exception("Unidade (Tenant) não identificada.");

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($action) {

        // --- LISTAR BARBEIROS ---
        case 'get_barbeiros':
            $barbeiros = Database::fetchAll(
                "SELECT id, nome FROM operadores WHERE tenant_id = ? AND nivel = 'OPERADOR' AND ativo = 1",
                [$tenantId]
            );
            echo json_encode(['success' => true, 'data' => $barbeiros]);
            break;

        // --- BUSCAR HORÁRIOS DISPONÍVEIS ---
        case 'get_slots':
            $operadorId = (int)($_GET['operador_id'] ?? 0);
            $dataAlvo = $_GET['data'] ?? date('Y-m-d'); // Ex: 2026-09-10

            if ($operadorId <= 0) throw new Exception("Barbeiro não selecionado.");

            // A. Busca a regra de horários do barbeiro para o dia da semana
            $diaSemana = (int)date('w', strtotime($dataAlvo));
            $regra = Database::fetch(
                "SELECT * FROM agenda_regras WHERE tenant_id = ? AND operador_id = ? AND dia_semana = ? AND ativo = 1 LIMIT 1",
                [$tenantId, $operadorId, $diaSemana]
            );

            if (!$regra) {
                echo json_encode(['success' => true, 'data' => [], 'message' => 'Barbeiro não atende neste dia.']);
                exit;
            }

            // B. Busca agendamentos já feitos para este dia
            $ocupados = Database::fetchAll(
                "SELECT DATE_FORMAT(data_agendamento, '%H:%i') as hora FROM senhas
                 WHERE tenant_id = ? AND operador_id = ? AND DATE(data_agendamento) = ? AND status IN ('AGENDADO', 'PRESENTE', 'CHAMANDO')",
                [$tenantId, $operadorId, $dataAlvo]
            );
            $horasOcupadas = array_column($ocupados, 'hora');

            // C. Gera os slots (ex: 08:00, 08:30...)
            $slots = [];
            $inicio = strtotime($regra['hora_inicio']);
            $fim = strtotime($regra['hora_fim']);
            $intervalo = ($regra['duracao_slot'] ?: 30) * 60;

            for ($i = $inicio; $i < $fim; $i += $intervalo) {
                $hora = date('H:i', $i);
                if (!in_array($hora, $horasOcupadas)) {
                    $slots[] = $hora;
                }
            }

            echo json_encode(['success' => true, 'data' => $slots]);
            break;

        // --- CRIAR AGENDAMENTO ---
        case 'book':
            if ($method !== 'POST') throw new Exception("Método não permitido.");

            $input = json_decode(file_get_contents('php://input'), true);
            $nome = strtoupper(trim($input['nome'] ?? ''));
            $whatsapp = preg_replace('/\D/', '', $input['whatsapp'] ?? '');
            $servicoId = (int)($input['servico_id'] ?? 0);
            $operadorId = (int)($input['operador_id'] ?? 0);
            $dataHora = $input['data_hora'] ?? ''; // Ex: 2026-09-10 14:00:00

            if (!$nome || !$whatsapp || !$dataHora || $servicoId <= 0) {
                throw new Exception("Dados incompletos para agendamento.");
            }

            // Validação de Duplicidade (Não deixa agendar 2x no mesmo horário)
            $check = Database::fetch(
                "SELECT id FROM senhas WHERE tenant_id = ? AND operador_id = ? AND data_agendamento = ? AND status = 'AGENDADO'",
                [$tenantId, $operadorId, $dataHora]
            );
            if ($check) throw new Exception("Este horário acabou de ser preenchido. Escolha outro.");

            // Inserção do Agendamento
            $uuid = bin2hex(random_bytes(16));
            $token = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

            Database::execute(
                "INSERT INTO senhas (tenant_id, uuid, servico_id, operador_id, nome_cliente, whatsapp, data_agendamento, cancel_token, status, tipo_atendimento, codigo, numero, prefixo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'AGENDADO', 'AGENDADO', 'WEB', 0, 'W')",
                [$tenantId, $uuid, $servicoId, $operadorId, $nome, $whatsapp, $dataHora, $token]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Agendamento confirmado!',
                'token' => $token,
                'uuid' => $uuid
            ]);
            break;

        default:
            throw new Exception("Ação inválida.");
    }

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
