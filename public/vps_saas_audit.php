<?php
/**
 * BT Queue - VPS SaaS Auditor (Diamond v4.2.0)
 * Este script verifica a integridade do isolamento de dados na VPS.
 */

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: text/html; charset=utf-8');
echo "<style>body { background: #081421; color: #fff; font-family: sans-serif; padding: 40px; }
      .card { background: #132238; padding: 20px; border-radius: 10px; border: 1px solid #1E3552; margin-bottom: 20px; }
      .green { color: #18C964; } .red { color: #FF4D4D; } .orange { color: #f5a524; }</style>";

echo "<h1>🕵️ Auditoria SaaS - Brandão Tech VPS</h1>";

try {
    // 1. Verifica Tenant Atual
    $host = $_SERVER['HTTP_HOST'];
    $tenant = Auth::getCurrentTenant();

    echo "<div class='card'>";
    echo "<h3>1. Identificação de Acesso</h3>";
    echo "URL Acessada: <b class='orange'>$host</b><br>";
    if ($tenant) {
        echo "Tenant Identificado: <b class='green'>{$tenant['slug']} (ID: {$tenant['id']})</b>";
    } else {
        echo "Tenant Identificado: <b class='red'>NENHUM (Falha no isolamento!)</b>";
    }
    echo "</div>";

    // 2. Verifica Tabela de Licenças
    echo "<div class='card'>";
    echo "<h3>2. Tabela de Licenças (Modo SaaS)</h3>";
    $licencas = Database::fetchAll("SELECT * FROM licencas");
    echo "<table border='1' width='100%'><tr><th>ID</th><th>Tenant ID</th><th>Status</th><th>Validade</th></tr>";
    foreach ($licencas as $l) {
        $color = ($l['status'] === 'ATIVA') ? 'green' : 'red';
        echo "<tr><td>{$l['id']}</td><td>{$l['tenant_id']}</td><td class='$color'>{$l['status']}</td><td>{$l['validade']}</td></tr>";
    }
    echo "</table>";
    echo "</div>";

    // 3. Verifica Conflitos de Dados
    echo "<div class='card'>";
    echo "<h3>3. Saúde do Banco MariaDB</h3>";
    $totalTenants = Database::fetch("SELECT COUNT(*) as total FROM tenants")['total'];
    $totalClientes = Database::fetch("SELECT COUNT(*) as total FROM clientes")['total'];
    echo "Total de Unidades: <b class='orange'>$totalTenants</b><br>";
    echo "Total de Clientes cadastrados: <b class='orange'>$totalClientes</b><br>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='card red'>Erro na auditoria: " . $e->getMessage() . "</div>";
}
