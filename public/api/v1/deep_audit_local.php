<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: application/json');

try {
    $tenants = Database::fetchAll("SELECT id, slug, nome FROM tenants");
    $operadores = Database::fetchAll("SELECT id, nome, login, tenant_id FROM operadores WHERE login IN ('admin', 'ricardo', 'israel')");

    $host = $_SERVER['HTTP_HOST'] ?? 'terminal';

    echo json_encode([
        'ambiente' => 'VM_LOCAL',
        'host_atual' => $host,
        'tenants' => $tenants,
        'operadores_suspeitos' => $operadores,
        'server_info' => $_SERVER
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
