<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain');

echo "=== AUDITORIA PROFUNDA MARIADB ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";

try {
    $db = Database::getInstance();
    echo "Conexão: OK\n";

    $tabelas = Database::fetchAll("SHOW TABLES");
    echo "Tabelas no banco:\n";
    foreach($tabelas as $t) {
        $nome = array_values($t)[0];
        $count = Database::fetch("SELECT COUNT(*) as total FROM $nome")['total'];
        echo "- $nome ($count registros)\n";

        if ($nome === 'clientes') {
             echo "  Colunas: " . implode(', ', Database::getTableColumns('clientes')) . "\n";
             $ultimos = Database::fetchAll("SELECT id, nome, whatsapp FROM clientes ORDER BY id DESC LIMIT 5");
             foreach($ultimos as $u) echo "    > ID: {$u['id']} | Nome: {$u['nome']} | Zap: {$u['whatsapp']}\n";
        }
    }

    $tenant = \BTQueue\Core\Auth::getCurrentTenant();
    echo "Tenant Ativo: " . ($tenant['slug'] ?? 'NENHUM') . " (ID: " . ($tenant['id'] ?? '0') . ")\n";

} catch (Exception $e) {
    echo "ERRO NA AUDITORIA: " . $e->getMessage() . "\n";
}
