<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    $db = Database::getInstance();

    echo "--- AUDITORIA (Aug 29/30) ---\n";
    $query = "SELECT * FROM auditoria WHERE created_at >= '2026-08-29 00:00:00' ORDER BY created_at DESC LIMIT 20";
    $results = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        echo "[{$row['created_at']}] Tenant: {$row['tenant_id']} | Action: {$row['acao']} | Details: {$row['detalhes']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
