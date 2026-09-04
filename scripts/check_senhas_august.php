<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    $db = Database::getInstance();

    // Query 1: Records on Aug 29 or 30
    $query1 = "SELECT id, tenant_id, created_at, finalizada_em, status FROM senhas
               WHERE (created_at >= '2026-08-29 00:00:00' AND created_at <= '2026-08-30 23:59:59')
               OR (finalizada_em >= '2026-08-29 00:00:00' AND finalizada_em <= '2026-08-30 23:59:59')";
    $results1 = $db->query($query1)->fetchAll(PDO::FETCH_ASSOC);

    // Query 2: tenant_id 1 recently (since Aug 28)
    $query2 = "SELECT id, created_at, status FROM senhas
               WHERE tenant_id = 1
               AND created_at >= '2026-08-28 00:00:00'";
    $results2 = $db->query($query2)->fetchAll(PDO::FETCH_ASSOC);

    echo "--- RECORDS ON AUG 29/30 ---\n";
    echo "Count: " . count($results1) . "\n";
    foreach ($results1 as $row) {
        $finished = $row['finalizada_em'] ?? 'N/A';
        echo "ID: {$row['id']} | Tenant: {$row['tenant_id']} | Created: {$row['created_at']} | Finished: {$finished} | Status: {$row['status']}\n";
    }

    echo "\n--- TENANT 1 RECENT RECORDS (Since Aug 28) ---\n";
    echo "Count: " . count($results2) . "\n";
    foreach ($results2 as $row) {
        echo "ID: {$row['id']} | Created: {$row['created_at']} | Status: {$row['status']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
