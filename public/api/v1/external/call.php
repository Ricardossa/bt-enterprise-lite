<?php

declare(strict_types=1);

/**
 * BT Integration Hub - Chamada Externa (Hospitalar) v1.1
 * Requer Token de Segurança no Header
 */

require_once __DIR__ . '/../../../../bootstrap.php';
use BTQueue\Core\Integration\IntegrationHubController;
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Apenas POST permitido.']);
    exit;
}

// --- VALIDAÇÃO DE SEGURANÇA ---
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$tokenEnviado = $headers['x-integration-token'] ?? '';

$config = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'integration_token'");
$tokenOficial = $config['valor'] ?? '';

if (empty($tokenOficial) || $tokenEnviado !== $tokenOficial) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado: Token de integração inválido ou não configurado.']);
    exit;
}

// --- PROCESSAMENTO DO PAYLOAD ---
$input = file_get_contents('php://input');
$payload = json_decode($input, true);

if (!$payload) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido.']);
    exit;
}

$hub = new IntegrationHubController();
$result = $hub->externalCall($payload);

if (!$result['success']) {
    http_response_code(500);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
