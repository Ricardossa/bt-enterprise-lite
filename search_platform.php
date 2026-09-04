<?php
declare(strict_types=1);

try {
    $dsn = 'mysql:host=127.0.0.1;dbname=bt_platform;charset=utf8mb4';
    $pdo = new PDO($dsn, 'bt_platform', 'BTPlatform2026!', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in bt_platform: " . implode(", ", $tables) . "\n";

    foreach ($tables as $table) {
        $cols = $pdo->query("DESCRIBE `$table`")->fetchAll();
        $textCols = [];
        foreach ($cols as $col) {
            $type = strtolower($col['Type']);
            if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
                $textCols[] = "`" . $col['Field'] . "`";
            }
        }
        if (empty($textCols)) continue;

        $where = implode(" LIKE '%:8080%' OR ", $textCols) . " LIKE '%:8080%'";
        $sql = "SELECT * FROM `$table` WHERE $where";
        $results = $pdo->query($sql)->fetchAll();

        if (!empty($results)) {
            echo "MATCH in bt_platform.$table:\n";
            print_r($results);
        }
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
