<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
header('Content-Type: application/json');
try {
    $tenants = Database::fetchAll("SELECT id, slug, nome FROM tenants");
    $ops = Database::fetchAll("SELECT id, nome, login, tenant_id FROM operadores WHERE login IN ('admin', 'ricardo', 'israel')");
    echo json_encode(['tenants' => $tenants, 'operadores' => $ops], JSON_PRETTY_PRINT);
} catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
