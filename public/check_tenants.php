<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

$tenants = Database::fetchAll("SELECT * FROM tenants");
$operadores = Database::fetchAll("SELECT id, tenant_id, login FROM operadores");

header('Content-Type: application/json');
echo json_encode([
    'host' => $_SERVER['HTTP_HOST'],
    'tenants' => $tenants,
    'operadores' => $operadores
], JSON_PRETTY_PRINT);
