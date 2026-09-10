<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;

function testHost($host) {
    $_SERVER['HTTP_HOST'] = $host;
    $tenant = Auth::getCurrentTenant();
    echo "Host: $host\n";
    echo "Tenant ID: " . ($tenant ? $tenant['id'] : 'NOT FOUND') . "\n";
    echo "Tenant Slug: " . ($tenant ? $tenant['slug'] : 'N/A') . "\n\n";
}

header('Content-Type: text/plain');
echo "--- TESTE DE RESOLUÇÃO DE TENANT ---\n\n";

try {
    testHost('lite.brandaotech.com.br');
    testHost('paradaobrigatoriavilas.brandaotech.com.br');

    echo "--- DADOS NO BANCO ---\n";
    $rows = Database::fetchAll("SELECT id, slug, nome FROM tenants");
    print_r($rows);
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
