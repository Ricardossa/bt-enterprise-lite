<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
$res = Database::fetchAll("SELECT * FROM tenants");
echo json_encode($res, JSON_PRETTY_PRINT);
