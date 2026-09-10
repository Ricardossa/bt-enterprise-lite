<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

try {
    echo "--- TENANTS ---\n";
    $tenants = Database::fetchAll("SHOW TABLES LIKE 'tenants'");
    if (empty($tenants)) {
        echo "TABELA 'tenants' NAO EXISTE!\n";
    } else {
        $rows = Database::fetchAll("SELECT * FROM tenants");
        print_r($rows);
    }

    echo "\n--- CURRENT TENANT ---\n";
    echo "Tenant ID: " . Auth::tenantId() . "\n";
    print_r(Auth::getCurrentTenant());

    echo "\n--- CLIENTES SCHEMA ---\n";
    $cols = Database::fetchAll("DESCRIBE clientes");
    print_r($cols);

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
