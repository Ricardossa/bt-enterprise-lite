<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    $db = Database::getInstance();
    $results = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo implode("\n", $results) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
