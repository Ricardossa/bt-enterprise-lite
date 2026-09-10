<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\ClientService;
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {
    $tenant = Auth::getCurrentTenant();
    if (!$tenant) {
        throw new Exception("Unidade não identificada.");
    }

    $service = new ClientService();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $uuid = $_GET['uuid'] ?? '';
        if (empty($uuid)) throw new Exception("UUID necessário.");

        $cliente = $service->buscarPorUuid($uuid, (int)$tenant['id']);
        if (!$cliente) {
            echo json_encode(['success' => false, 'message' => 'Perfil não encontrado.']);
            exit;
        }

        $saldo = $service->getSaldo((int)$cliente['id'], (int)$tenant['id']);
        $config = $service->getConfig((int)$tenant['id']);

        // [v3.0.2] Busca por ID ou UUID (Garante detecção mesmo se o ID falhou na reserva)
        $agendamento = Database::fetch(
            "SELECT uuid, codigo, data_agendamento as hora, status
             FROM senhas
             WHERE (cliente_id = ? OR cliente_uuid = ?)
             AND tenant_id = ? AND DATE(data_agendamento) = CURDATE()
             AND status IN ('AGENDADO', 'PRESENTE', 'CHAMANDO') LIMIT 1",
            [(int)$cliente['id'], $uuid, (int)$tenant['id']]
        );

        if ($agendamento) {
            $agendamento['hora'] = date('H:i', strtotime($agendamento['hora']));
        }

        // [v2.5.2] Busca Logo e Nome da Unidade para o Branding do App
        $unitLogo = Database::fetch("SELECT valor FROM configuracoes WHERE tenant_id = ? AND chave = 'logo_url' LIMIT 1", [(int)$tenant['id']])['valor'] ?? '';
        $unitName = Database::fetch("SELECT valor FROM configuracoes WHERE tenant_id = ? AND chave = 'empresa' LIMIT 1", [(int)$tenant['id']])['valor'] ?? 'Barbearia';

        echo json_encode([
            'success' => true,
            'cliente' => $cliente,
            'unit' => [
                'nome' => $unitName,
                'logo' => $unitLogo
            ],
            'fidelidade' => [
                'saldo' => $saldo,
                'config' => $config
            ],
            'agendamento' => $agendamento // Injetado para o Mobile
        ]);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $_GET['action'] ?? 'registrar';

        if ($action === 'buscar_whatsapp') {
            $whatsapp = $input['whatsapp'] ?? '';
            $cliente = $service->buscarPorWhatsapp($whatsapp, (int)$tenant['id']);

            if ($cliente) {
                echo json_encode(['success' => true, 'cliente' => $cliente]);
            } else {
                echo json_encode(['success' => false, 'message' => 'WhatsApp não cadastrado.']);
            }
            exit;
        }

        if ($action === 'creditar') {
            Auth::protegerAPI('ADMIN');
            $clienteId = (int)($input['cliente_id'] ?? 0);
            if ($clienteId <= 0) throw new Exception("ID do cliente inválido.");

            $ok = $service->creditarPonto($clienteId, (int)$tenant['id'], 0, 'Crédito manual via painel');
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Ponto creditado!' : 'Erro ao creditar ponto.']);
            exit;
        }

        $resultado = $service->registrar($input, (int)$tenant['id']);
        echo json_encode($resultado);
        exit;
    }

    if ($method === 'PUT') {
        Auth::protegerAPI('ADMIN');
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID inválido.");

        $ok = $service->atualizar($id, $input, (int)$tenant['id']);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Cliente atualizado!' : 'Erro ao atualizar.']);
        exit;
    }

    if ($method === 'DELETE') {
        Auth::protegerAPI('ADMIN');
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID inválido.");

        $ok = $service->excluir($id, (int)$tenant['id']);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Cliente removido!' : 'Erro ao excluir.']);
        exit;
    }

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
