<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    $db = Database::getInstance();

    echo "--- TENANTS ---\n";
    $tenants = $db->query("SELECT id, nome, status FROM tenants")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tenants as $t) {
        echo "ID: {$t['id']} | Name: {$t['nome']} | Status: {$t['status']}\n";
    }

    echo "\n--- SYNC QUEUE ---\n";
    $sync = $db->query("SELECT COUNT(*) as count FROM sync_queue")->fetch(PDO::FETCH_ASSOC);
    echo "Total items in sync_queue: {$sync['count']}\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
