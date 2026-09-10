<?php
$dsn = 'mysql:host=127.0.0.1;charset=utf8mb4';
$pdo = new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$dbs = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($dbs as $db) {
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) continue;
    echo "Database: $db\n";
    $tables = $pdo->query("SHOW TABLES FROM `$db`")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        echo "  - $t\n";
    }
}
