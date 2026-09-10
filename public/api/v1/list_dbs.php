<?php
$host = '127.0.0.1';
$username = 'bt_saas_user';
$password = 'BrandaoElite2026!';
try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $res = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($res, JSON_PRETTY_PRINT);
} catch (Exception $e) { echo $e->getMessage(); }
