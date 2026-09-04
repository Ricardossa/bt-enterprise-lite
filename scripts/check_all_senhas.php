<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    $db = Database::getInstance();
    $query = "SELECT id, tenant_id, created_at, status FROM senhas ORDER BY created_at DESC LIMIT 10";
    $results = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

    echo "--- LAST 10 RECORDS IN SENHAS ---\n";
    foreach ($results as $row) {
        echo "ID: {$row['id']} | Tenant: {$row['tenant_id']} | Created: {$row['created_at']} | Status: {$row['status']}\n";
    }

    // Also check tenant 1 specifically
    $query2 = "SELECT COUNT(*) as count FROM senhas WHERE tenant_id = 1";
    $count1 = $db->query($query2)->fetch(PDO::FETCH_ASSOC)['count'];
    echo "\nTotal records for tenant 1: $count1\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
