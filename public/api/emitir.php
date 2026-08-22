<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\QueueService;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);

if (!$dados) {
    $dados = $_POST;
}

$prefixo = strtoupper(trim($dados['prefixo'] ?? 'F'));
$servicoId = (int)($dados['servico_id'] ?? 0);
$clienteUuid = trim($dados['cliente_uuid'] ?? 'LOCAL');

if ($servicoId <= 0) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Serviço não informado.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$queue = new QueueService();

echo json_encode(
    $queue->emitir(
        $prefixo,
        $servicoId,
        $clienteUuid
    ),
    JSON_UNESCAPED_UNICODE
);