<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;
header('Content-Type: application/json');
try {
    $db = Database::getInstance();
    $res = Database::fetch("SELECT nome FROM tenants WHERE id = 1");
    $opCount = Database::fetch("SELECT COUNT(*) as total FROM operadores")['total'];
    echo json_encode([
        'success' => true,
        'engine' => 'MARIADB',
        'tenant' => $res['nome'],
        'operators_count' => $opCount,
        'php_version' => PHP_VERSION
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
