<?php
declare(strict_types=1);

try {
    $dsn = 'mysql:host=127.0.0.1;dbname=bt_platform;charset=utf8mb4';
    $pdo = new PDO($dsn, 'bt_platform', 'BTPlatform2026!', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "--- 1. Check instalacoes for ID 41 ---\n";
    $stmt = $pdo->prepare("SELECT id, empresa_id, nome, status FROM instalacoes WHERE id = ?");
    $stmt->execute([41]);
    $inst41 = $stmt->fetch();
    if ($inst41) {
        print_r($inst41);
    } else {
        echo "Record with ID 41 not found in 'instalacoes'.\n";
    }

    echo "\n--- 2. Check empresa for ID 22 ---\n";
    $stmt = $pdo->prepare("SELECT id, nome_fantasia FROM empresas WHERE id = ?");
    $stmt->execute([22]);
    $emp22 = $stmt->fetch();
    if ($emp22) {
        print_r($emp22);
    } else {
        echo "Empresa with ID 22 not found in 'empresas'.\n";
    }

    echo "\n--- 3. Search for 'Vilas Premium' in instalacoes ---\n";
    $stmt = $pdo->prepare("SELECT id, empresa_id, nome, status FROM instalacoes WHERE nome LIKE ?");
    $stmt->execute(['%Vilas Premium%']);
    $others = $stmt->fetchAll();
    if ($others) {
        print_r($others);
    } else {
        echo "No other records found in 'instalacoes' with name 'Vilas Premium'.\n";
    }

    echo "\n--- 4. Check 'tenants' for ID 41 or 'paradaobrigatoriavilas' ---\n";
    $stmt = $pdo->prepare("SELECT id, nome, slug FROM tenants WHERE id = ? OR slug = ?");
    $stmt->execute([41, 'paradaobrigatoriavilas']);
    $tenants = $stmt->fetchAll();
    if ($tenants) {
        print_r($tenants);
    } else {
        echo "No records found in 'tenants' for ID 41 or slug 'paradaobrigatoriavilas'.\n";
    }

} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
