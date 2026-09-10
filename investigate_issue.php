<?php
declare(strict_types=1);

try {
    $dsn = 'mysql:host=127.0.0.1;dbname=bt_platform;charset=utf8mb4';
    $pdo = new PDO($dsn, 'bt_platform', 'BTPlatform2026!', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "--- Query 1: Installation ID 41 ---\n";
    $stmt = $pdo->prepare("SELECT * FROM instalacoes WHERE id = 41");
    $stmt->execute();
    print_r($stmt->fetchAll());

    echo "\n--- Query 2: Company ID 22 ---\n";
    $stmt = $pdo->prepare("SELECT * FROM empresas WHERE id = 22");
    $stmt->execute();
    print_r($stmt->fetchAll());

    echo "\n--- Query 3: Companies LIKE 'paradaobrigatoria' ---\n";
    $stmt = $pdo->prepare("SELECT * FROM empresas WHERE nome LIKE '%paradaobrigatoria%'");
    $stmt->execute();
    print_r($stmt->fetchAll());

    echo "\n--- Query 4: Tenants LIKE 'paradaobrigatoriavilas' ---\n";
    $stmt = $pdo->prepare("SELECT * FROM tenants WHERE name LIKE '%paradaobrigatoriavilas%'");
    $stmt->execute();
    print_r($stmt->fetchAll());

} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
