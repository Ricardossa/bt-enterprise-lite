<?php
require_once __DIR__ . '/../bootstrap.php';
$uuid = \BTQueue\Core\Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'uuid' LIMIT 1")['valor'] ?? 'NÃO ENCONTRADO';
$token = \BTQueue\Core\Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'token' LIMIT 1")['valor'] ?? 'NÃO ENCONTRADO';
$url = \BTQueue\Core\Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'master_url' LIMIT 1")['valor'] ?? 'NÃO ENCONTRADO';
$licenca = \BTQueue\Core\Database::fetch("SELECT * FROM licencas LIMIT 1");

header('Content-Type: application/json');
echo json_encode([
    'db_uuid' => $uuid,
    'db_token' => $token,
    'db_url' => $url,
    'db_license_status' => $licenca['status'] ?? 'N/A',
    'expected_uuid' => '0fcda877-6e4c-4be2-bedc-74cbe4555bd1',
    'product_sent' => 'BT_QUEUE_ENTERPRISE_LITE'
]);
