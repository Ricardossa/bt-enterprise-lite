<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\QueueService;
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        \BTQueue\Core\Auth::protegerAPI();

        // Detecta colunas da tabela senhas (SaaS Safe)
        $cols = Database::getTableColumns('senhas');
        $campoCodigo = in_array('codigo', $cols) ? 's.codigo' : 's.senha';

        $fila = Database::fetchAll(
            "SELECT s.*, $campoCodigo as codigo, sv.nome AS servico_nome
             FROM senhas s
             LEFT JOIN servicos sv ON sv.id = s.servico_id
             WHERE s.status='AGUARDANDO' AND s.tenant_id = ?
             ORDER BY s.id",
            [Auth::tenantId()]
        );

        echo json_encode([
            'success' => true,
            'data' => $fila
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if ($method === 'POST') {

        $dados = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $servicoId = (int)($dados['servico_id'] ?? 0);
        $operadorId = (int)($dados['operador_id'] ?? 0);
        $valorTotal = (float)($dados['valor_total'] ?? 0);
        $servicosAdicionais = $dados['servicos_adicionais'] ?? [];

        $nomeCliente = trim((string)($dados['nome_cliente'] ?? ''));
        $deviceId = trim((string)($dados['device_id'] ?? ''));
        $tokenEnviado = trim((string)($dados['t'] ?? ''));
        $tipoAtendimento = strtoupper(trim((string)($dados['tipo'] ?? 'NORMAL')));

        if (!in_array($tipoAtendimento, ['NORMAL', 'PRIORITARIO'])) {
            $tipoAtendimento = 'NORMAL';
        }

        // --- VALIDAÇÃO DE SEGURANÇA (Anti-Fila Remota & Horário de Atendimento) ---
        if (!\BTQueue\Core\Auth::autenticado()) {
            $tenantId = \BTQueue\Core\Auth::tenantId();

            // 1. Verificação de Horário de Expediente
            $config = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE chave IN ('opening_time', 'closing_time', 'qr_security_salt') AND tenant_id = ?", [$tenantId]);
            $cfg = [];
            foreach ($config as $c) { $cfg[$c['chave']] = $c['valor']; }

            $agora = date('H:i');
            $abertura = $cfg['opening_time'] ?? '00:00';
            $fechamento = $cfg['closing_time'] ?? '23:59';

            if ($agora < $abertura || $agora > $fechamento) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => "❌ ATENDIMENTO ENCERRADO. Nosso horário de funcionamento é das $abertura às $fechamento."
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 2. Verificação de Token do QR Code (Híbrido v4.1.9)
            $salt = $cfg['qr_security_salt'] ?? 'default_salt';
            $validToken = false;
            $tokenInvalido = false;

            if (!empty($tokenEnviado)) {
                for ($i = 0; $i <= 10; $i++) { // Janela de 10 minutos (v4.2.1)
                    $checkHash = md5($salt . date('YmdHi', strtotime("-$i minutes")));
                    if (hash_equals($checkHash, $tokenEnviado)) {
                        $validToken = true;
                        break;
                    }
                }
                if (!$validToken) $tokenInvalido = true;
            }

            // 3. [LITE v4.1.9] CERCA DE GPS (TRAVA DE DISTÂNCIA)
            $loc = [];
            $locRows = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE chave IN ('location_lat', 'location_lng', 'location_max_distance', 'location_check_enabled') AND tenant_id = ?", [$tenantId]);
            foreach ($locRows as $lr) { $loc[$lr['chave']] = $lr['valor']; }

            $isGpsEnabled = (($loc['location_check_enabled'] ?? '0') === '1' && !empty($loc['location_lat']));

            // --- LÃ“GICA DE DECISÃO DE ACESSO ---
            // SÃ³ bloqueia se o Token for invÃ¡lido E o GPS nÃ£o puder validar a presenÃ§a.
            if ($tokenInvalido && !$isGpsEnabled) {
                // Se nÃ£o tem GPS configurado, nÃ£o podemos barrar o Totem fÃ­sico por delay de tempo.
                // Mas avisamos para garantir que o cliente escaneie o mais novo.
            }

            if ($isGpsEnabled) {
                $userLat = (float)($dados['lat'] ?? 0);
                $userLng = (float)($dados['lng'] ?? 0);
                $maxDist = (int)($loc['location_max_distance'] ?? 150);

                if ($userLat === 0.0 || $userLng === 0.0) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => '📍 LOCALIZAÇÃO NECESSÁRIA: Permita o acesso ao GPS para retirar sua senha.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $earthRadius = 6371000;
                $latFrom = deg2rad((float)$loc['location_lat']); $lonFrom = deg2rad((float)$loc['location_lng']);
                $latTo = deg2rad($userLat); $lonTo = deg2rad($userLng);
                $angle = 2 * asin(sqrt(pow(sin(($latTo - $latFrom) / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin(($lonTo - $lonFrom) / 2), 2)));
                $distance = $angle * $earthRadius;

                if ($distance > $maxDist) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => "❌ VOCÊ ESTÁ MUITO LONGE: Sua distância atual é de " . round($distance) . "m. A distância máxima permitida é de {$maxDist}m."], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
        }

        if ($servicoId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Serviço não informado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // [LITE v4.1.0] Blindagem SaaS: Garante que o serviço pertence à Unidade atual
        $tenantId = \BTQueue\Core\Auth::tenantId();
        $servico = Database::fetch("SELECT prefixo, nome FROM servicos WHERE id=? AND tenant_id = ? LIMIT 1", [$servicoId, $tenantId]);

        if (!$servico) {
            echo json_encode(['success' => false, 'message' => 'Serviço não encontrado nesta unidade.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($deviceId !== '') {
        $cols = Database::getTableColumns('senhas');
        $campoCodigo = in_array('codigo', $cols) ? 'codigo' : 'senha';

            $senhaExistente = Database::fetch(
                "SELECT $campoCodigo as codigo, cliente_uuid, status
                 FROM senhas
                 WHERE device_id = ?
                 AND servico_id = ?
                 AND status IN ('AGUARDANDO','CHAMANDO','CONGELADA')
                 AND tenant_id = ?
                 AND DATE(created_at) = CURDATE()
                 ORDER BY id DESC
                 LIMIT 1",
                [$deviceId, $servicoId, \BTQueue\Core\Auth::tenantId()]
            );

            if ($senhaExistente) {
                echo json_encode([
                    'success' => true,
                    'modo' => 'existente',
                    'message' => 'Senha já existente.',
                    'data' => $senhaExistente
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $clienteUuid = bin2hex(random_bytes(16));

        // [LITE v3.5.0] Resolve Cliente ID via Fidelidade
        $clienteId = 0;
        $tenantId = \BTQueue\Core\Auth::tenantId();
        if (!empty($deviceId)) {
            $cService = new \BTQueue\Core\ClientService();
            $cli = $cService->buscarPorUuid($deviceId, (int)$tenantId);
            if ($cli) $clienteId = (int)$cli['id'];
        }

        $queue = new QueueService();

        $resultado = $queue->emitir(
            $servico['prefixo'],
            $servicoId,
            $clienteUuid,
            $deviceId,
            $tipoAtendimento,
            $operadorId,
            $valorTotal,
            $servicosAdicionais,
            $nomeCliente,
            $clienteId
        );

        if ($resultado['success']) {
            $resultado['servico_nome'] = $servico['nome'];
            $resultado['cliente_uuid'] = $clienteUuid;
            $resultado['servico_id'] = $servicoId; // v2.3.1: Vital para ocultação de botão

            echo json_encode([
                'success' => true,
                'message' => 'Senha gerada com sucesso!',
                'data' => $resultado
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
