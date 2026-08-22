<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;
use BTQueue\Core\Config;

header('Content-Type: text/plain');

echo "--- DEBUG AMBIENTE ---\n";
echo "HOST: " . ($_SERVER['HTTP_HOST'] ?? 'CLI') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'CLI') . "\n";
echo "SESSION: "; print_r($_SESSION); echo "\n";

try {
    $db = Database::getInstance();
    echo "CONEXAO DB: OK\n";

    // Tenta descobrir tabelas
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "TABELAS ENCONTRADAS: " . implode(', ', $tables) . "\n";

    $tenant = Auth::getCurrentTenant();
    echo "TENANT ATUAL: "; print_r($tenant); echo "\n";

    if (in_array('clientes', $tables)) {
        $count = $db->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
        echo "TOTAL CLIENTES: $count\n";
    }

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
