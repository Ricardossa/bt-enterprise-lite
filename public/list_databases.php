<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    $db = Database::getInstance();
    $dbs = $db->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['databases' => $dbs]);
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
