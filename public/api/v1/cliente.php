<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\ClientService;

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

        echo json_encode([
            'success' => true,
            'cliente' => $cliente,
            'fidelidade' => [
                'saldo' => $saldo,
                'config' => $config
            ]
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

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
