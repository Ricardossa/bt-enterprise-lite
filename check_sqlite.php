<?php
try {
    $dbPath = 'Y:/bt-platform/database/platform.db';
    if (!file_exists($dbPath)) {
        die("File not found: $dbPath\n");
    }
    $pdo = new PDO("sqlite:$dbPath");
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in platform.db: " . implode(", ", $tables) . "\n";

    foreach ($tables as $table) {
        $cols = $pdo->query("PRAGMA table_info(`$table`)")->fetchAll();
        $textCols = [];
        foreach ($cols as $col) {
            $type = strtolower($col['type']);
            if (strpos($type, 'char') !== false || strpos($type, 'text') !== false || $type === '') {
                $textCols[] = "`" . $col['name'] . "`";
            }
        }
        if (empty($textCols)) continue;

        $where = implode(" LIKE '%:8080%' OR ", $textCols) . " LIKE '%:8080%'";
        $sql = "SELECT * FROM `$table` WHERE $where";
        $results = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($results)) {
            echo "MATCH in $table:\n";
            print_r($results);
        }
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
