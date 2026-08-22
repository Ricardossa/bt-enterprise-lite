<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\ScheduleService;
use BTQueue\Core\Database;
use BTQueue\Core\Auth;
use BTQueue\Core\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    $service = new ScheduleService();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    // [SaaS] Resolve o Tenant
    $tenantId = Auth::tenantId();

    // [LITE v1.5.0] LISTAR TODOS OS BARBEIROS (PÚBLICO - INÍCIO DO FUNIL)
    if ($method === 'GET' && $action === 'listar_barbeiros') {
        if ($tenantId <= 0) throw new Exception("Unidade não identificada.");

        $barbeiros = Database::fetchAll("SELECT id, nome, foto_url FROM operadores WHERE tenant_id = ? AND ativo = 1 AND nivel = 'OPERADOR' ORDER BY nome ASC", [$tenantId]);
        echo json_encode(['success' => true, 'data' => $barbeiros]);
        exit;
    }

    // [LITE v1.5.0] LISTAR SERVIÇOS DE UM BARBEIRO ESPECÍFICO (v3.5.7: Suporte FILA GERAL id=0)
    if ($method === 'GET' && $action === 'servicos_por_barbeiro') {
        if ($tenantId <= 0) throw new Exception("Unidade não identificada.");

        $operadorId = (int)($_GET['operador_id'] ?? 0);

        if ($operadorId === 0) {
            // Se for Fila Geral, retorna TODOS os serviços ativos da unidade
            $servicos = Database::fetchAll("SELECT * FROM servicos WHERE tenant_id = ? AND ativo = 1 ORDER BY nome ASC", [$tenantId]);
        } else {
            $servicos = Database::fetchAll("
                SELECT s.*
                FROM servicos s
                JOIN operador_servicos os ON os.servico_id = s.id
                WHERE os.operador_id = ? AND s.tenant_id = ? AND s.ativo = 1
                ORDER BY s.nome ASC
            ", [$operadorId, $tenantId]);
        }

        echo json_encode(['success' => true, 'data' => $servicos]);
        exit;
    }

    // 2. BUSCAR SLOTS DISPONÍVEIS (PÚBLICO)
    if ($method === 'GET' && isset($_GET['operador_id'], $_GET['data'])) {
        if ($tenantId <= 0) {
             echo json_encode(['success' => false, 'message' => 'Unidade não identificada.', 'debug_host' => $_SERVER['HTTP_HOST']]);
             exit;
        }

        $operadorId = (int)$_GET['operador_id'];
        $data = $_GET['data']; // YYYY-MM-DD

        $slots = $service->getSlotsDisponiveis($operadorId, $data);

        // v3.3.3: Mensagem amigável se não houver slots
        if (empty($slots)) {
            echo json_encode(['success' => true, 'data' => [], 'message' => 'Nenhum horário disponível para esta data.']);
        } else {
            echo json_encode(['success' => true, 'data' => $slots]);
        }
        exit;
    }

    // --- AÇÕES ADMINISTRATIVAS (REQUER LOGIN) ---

    // 3. BUSCAR REGRAS DE UM PROFISSIONAL + CONFIGURAÇÃO GLOBAL
    if ($method === 'GET' && $action === 'get_regras') {
        Auth::protegerAPI('ADMIN');
        $operadorId = (int)($_GET['operador_id'] ?? 0);
        $regras = Database::fetchAll("SELECT * FROM agenda_regras WHERE operador_id = ? ORDER BY dia_semana ASC", [$operadorId]);

        // Busca também o horizonte global e radar (v6.9)
        $tenantId = Auth::tenantId();
        $horizonte = Database::fetch("SELECT valor FROM configuracoes WHERE tenant_id = ? AND chave = 'agenda_horizonte' LIMIT 1", [$tenantId]);
        $radarEnabled = Database::fetch("SELECT valor FROM configuracoes WHERE tenant_id = ? AND chave = 'radar_enabled' LIMIT 1", [$tenantId]);
        $radarTolerance = Database::fetch("SELECT valor FROM configuracoes WHERE tenant_id = ? AND chave = 'radar_tolerance' LIMIT 1", [$tenantId]);

        echo json_encode([
            'success' => true,
            'data' => $regras,
            'horizonte' => $horizonte ? (int)$horizonte['valor'] : 30,
            'radar_enabled' => $radarEnabled ? $radarEnabled['valor'] : "0",
            'radar_tolerance' => $radarTolerance ? $radarTolerance['valor'] : "15"
        ]);
        exit;
    }

    // 4. SALVAR REGRAS DE UM PROFISSIONAL + CONFIGURAÇÃO GLOBAL
    if ($method === 'POST' && $action === 'save_regras') {
        Auth::protegerAPI('ADMIN');

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);

        if (!$input) {
            $input = $_POST;
        }

        $operadorId = (int)($input['operador_id'] ?? 0);
        $regras = $input['regras'] ?? [];
        $horizonte = (int)($input['horizonte'] ?? 30);
        $radarEnabled = (string)($input['radar_enabled'] ?? "0");
        $radarTolerance = (string)($input['radar_tolerance'] ?? "15");

        if (!$operadorId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do profissional inválido.']);
            exit;
        }

        try {
            $db = Database::getInstance();
            $tenantId = Auth::tenantId();

            // 1. Atualiza Configurações Globais (v6.9)
            Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (?, 'agenda_horizonte', ?, 'NUMBER')", [$tenantId, (string)$horizonte]);
            Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (?, 'radar_enabled', ?, 'BOOLEAN')", [$tenantId, $radarEnabled]);
            Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (?, 'radar_tolerance', ?, 'NUMBER')", [$tenantId, $radarTolerance]);

            // Inicia operação atômica de troca de regras
            Database::beginImmediate();

            // 1. Limpa regras antigas do profissional
            $stmtDel = $db->prepare("DELETE FROM agenda_regras WHERE tenant_id = ? AND operador_id = ?");
            $stmtDel->execute([$tenantId, $operadorId]);

            // v3.1.2: Busca um serviço válido para evitar erro de Chave Estrangeira (FK)
            $defaultServico = Database::fetch("SELECT servico_id FROM operadores WHERE id = ?", [$operadorId])['servico_id'] ?? 0;
            if ($defaultServico <= 0) {
                $defaultServico = Database::fetch("SELECT id FROM servicos WHERE ativo = 1 LIMIT 1")['id'] ?? 1;
            }

            // 2. Insere novas regras (v6.1 Diamond Support)
            $stmtIns = $db->prepare("INSERT INTO agenda_regras (
                tenant_id, operador_id, servico_id, dia_semana, hora_inicio, hora_fim, duracao_slot,
                liberacao_dia_semana, liberacao_hora_inicio, liberacao_hora_fim, ativo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($regras as $r) {
                if (!isset($r['dia_semana'])) continue;

                $libDia = (isset($r['liberacao_dia']) && $r['liberacao_dia'] !== "") ? (int)$r['liberacao_dia'] : null;

                $stmtIns->execute([
                    $tenantId,
                    $operadorId,
                    $defaultServico, // v3.1.2: Agora usa um ID real em vez de 0
                    (int)$r['dia_semana'],
                    (string)($r['hora_inicio'] ?? '08:00'),
                    (string)($r['hora_fim'] ?? '18:00'),
                    (int)($r['duracao_slot'] ?? 30),
                    $libDia,
                    (string)($r['liberacao_inicio'] ?? '00:00'),
                    (string)($r['liberacao_fim'] ?? '23:59'),
                    (int)($r['ativo'] ?? 0)
                ]);
            }

            Database::commit();
            Logger::info("Agenda do profissional $operadorId atualizada.", ['regras' => count($regras)], 'agenda');

            echo json_encode(['success' => true, 'message' => 'Agenda salva com sucesso!']);
            exit;
        } catch (Throwable $dbError) {
            Database::rollback();
            Logger::error("Falha ao salvar agenda: " . $dbError->getMessage(), [], 'agenda');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno no banco de dados.']);
            exit;
        }
    }

    // --- MÓDULO DE SUSPENSÕES (ADMIN) ---

    if ($method === 'GET' && $action === 'get_suspensoes') {
        Auth::protegerAPI('ADMIN');
        $lista = Database::fetchAll("SELECT * FROM agenda_suspensoes WHERE tenant_id = ? AND data_fim >= CURDATE() ORDER BY data_fim DESC", [Auth::tenantId()]);
        echo json_encode(['success' => true, 'data' => $lista]);
        exit;
    }

    if ($method === 'POST' && $action === 'add_suspensao') {
        Auth::protegerAPI('ADMIN');
        $input = json_decode(file_get_contents('php://input'), true);
        $nome = strtoupper(trim((string)($input['nome'] ?? '')));
        $dias = (int)($input['dias'] ?? 14);

        if (!$nome) throw new Exception("Nome é obrigatório.");

        $dataFim = date('Y-m-d', strtotime("+$dias days"));
        Database::execute(
            "REPLACE INTO agenda_suspensoes (tenant_id, identificador, motivo, data_fim) VALUES (?, ?, 'Reincidência de faltas sem aviso prévio', ?)",
            [Auth::tenantId(), $nome, $dataFim]
        );

        echo json_encode(['success' => true, 'message' => 'Fornecedor suspenso com sucesso!']);
        exit;
    }

    if ($method === 'POST' && $action === 'remove_suspensao') {
        Auth::protegerAPI('ADMIN');
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        Database::execute("DELETE FROM agenda_suspensoes WHERE id = ?", [$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // 5. REALIZAR CHECK-IN (PÚBLICO NO TOTEM)
    if ($method === 'POST' && $action === 'checkin') {
        $input = json_decode(file_get_contents('php://input'), true);
        $query = strtoupper(trim((string)($input['query'] ?? '')));
        $hoje = date('Y-m-d');

        if (!$query) throw new Exception("Digite seu nome ou código.");

        // [LITE v3.3.5] Verifica regra de liberação antes do check-in
        $tenantId = Auth::tenantId();
        $releaseMode = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'booking_release_mode' AND tenant_id = ? LIMIT 1", [$tenantId])['valor'] ?? 'immediate';

        // Busca agendamento para HOJE que ainda não foi atendido
        $agendamento = Database::fetch(
            "SELECT * FROM senhas
             WHERE status IN ('AGENDADO', 'PRESENTE')
             AND tenant_id = ?
             AND DATE(data_agendamento) = ?
             AND (cancel_token = ? OR nome_cliente = ?)
             LIMIT 1",
            [$tenantId, $hoje, $query, $query]
        );

        if (!$agendamento) {
            echo json_encode(['success' => false, 'message' => 'Agendamento não localizado para hoje.']);
            exit;
        }

        // Se for modo 'after_payment' e não estiver pago, bloqueia check-in
        if ($releaseMode === 'after_payment' && $agendamento['pagamento_status'] !== 'PAGO' && (float)$agendamento['valor_total'] > 0) {
            echo json_encode(['success' => false, 'message' => '❌ Agendamento pendente de pagamento. Por favor, realize o PIX primeiro.']);
            exit;
        }

        // Marca como PRESENTE (Check-in realizado)
        Database::execute(
            "UPDATE senhas SET status = 'PRESENTE', updated_at = NOW() WHERE id = ?",
            [$agendamento['id']]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Check-in realizado com sucesso!',
            'data' => [
                'id' => $agendamento['id'],
                'codigo' => $agendamento['codigo'],
                'uuid' => $agendamento['uuid'],
                'cliente_uuid' => $agendamento['uuid'], // Alias para compatibilidade mobile
                'nome_cliente' => $agendamento['nome_cliente'],
                'hora' => date('H:i', strtotime($agendamento['data_agendamento']))
            ]
        ]);
        exit;
    }

    if ($method === 'GET' && $action === 'check_status') {
        $uuid = $_GET['uuid'] ?? '';
        $senha = Database::fetch("SELECT status, pagamento_status FROM senhas WHERE uuid = ? LIMIT 1", [$uuid]);
        if (!$senha) {
            echo json_encode(['success' => false, 'message' => 'Nao encontrado.']);
            exit;
        }

        // v1.3.0-LITE: No modo teste, permite que o cliente "pule" o pagamento para testar o sucesso
        $isTest = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'mercadopago_test_mode' LIMIT 1")['valor'] ?? '0';
        if ($isTest === '1' && isset($_GET['simulate_pay'])) {
            Database::execute("UPDATE senhas SET pagamento_status = 'PAGO', status = 'CONFIRMADO' WHERE uuid = ?", [$uuid]);
            echo json_encode(['success' => true, 'status' => 'PAGO']);
            exit;
        }

        echo json_encode(['success' => true, 'status' => $senha['pagamento_status']]);
        exit;
    }

    // [LITE v2.8.8] NOTIFICAR PAGAMENTO MANUAL (CHAVE ESTATICA)
    if ($method === 'POST' && $action === 'confirm_pay') {
        $input = json_decode(file_get_contents('php://input'), true);
        $uuid = $input['uuid'] ?? '';
        $tenantId = Auth::tenantId();

        if (!$uuid || $tenantId <= 0) throw new Exception("Identificador invalido.");

        // No Lite, confiamos na notificacao do cliente (que enviara o print via zap em seguida)
        Database::execute("UPDATE senhas SET pagamento_status = 'PAGO' WHERE uuid = ? AND tenant_id = ?", [$uuid, $tenantId]);

        // [LITE v3.5.0] Crédito de Pontos de Fidelidade
        $senha = Database::fetch("SELECT id, cliente_id, servicos_desc FROM senhas WHERE uuid = ? AND tenant_id = ?", [$uuid, $tenantId]);
        if ($senha && (int)$senha['cliente_id'] > 0) {
            $cService = new \BTQueue\Core\ClientService();
            $cService->creditarPonto((int)$senha['cliente_id'], (int)$tenantId, (int)$senha['id'], "Corte: " . ($senha['servicos_desc'] ?: 'Barbearia'));
        }

        echo json_encode(['success' => true, 'message' => 'Pagamento notificado ao barbeiro!']);
        exit;
    }

    // 6. REALIZAR RESERVA (PÚBLICO)
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $servicoId = (int)($input['servico_id'] ?? 0);
        $servicoIds = $input['servico_ids'] ?? $input['servicos'] ?? [$servicoId]; // Suporta array de IDs (Combo)

        $nome = strtoupper(trim((string)($input['nome_cliente'] ?? '')));
        $data = trim((string)($input['data'] ?? ''));
        $hora = trim((string)($input['hora'] ?? ''));
        $whatsapp = trim((string)($input['whatsapp'] ?? ''));
        $deviceId = trim((string)($input['device_id'] ?? ''));
        $operadorId = (int)($input['operador_id'] ?? 0);

        // [LITE v3.5.0] Resolve Cliente ID via Fidelidade
        $clienteId = 0;
        if (!empty($deviceId)) {
            $cService = new \BTQueue\Core\ClientService();
            $cli = $cService->buscarPorUuid($deviceId, (int)$tenantId);
            if ($cli) $clienteId = (int)$cli['id'];
        }

        if (empty($servicoIds) || !$nome || !$data || !$hora) {
            throw new Exception("Preencha todos os campos obrigatórios.");
        }

        $dataHora = "$data $hora:00";
        $resultado = $service->reservar($servicoIds, $nome, $dataHora, $whatsapp, $deviceId, $operadorId, $clienteId);

        echo json_encode($resultado);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
