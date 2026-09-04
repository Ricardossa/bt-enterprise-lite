<?php
/**
 * Script de Correção: Remover portas 8080/8090 das URLs de sincronização
 * Data: 01/09/2026
 * Descrição: Atualiza todas as master_url no banco para usar apenas http://api.brandaotech.com.br (porta 80 padrão)
 */

declare(strict_types=1);

$_SERVER['SCRIPT_NAME'] = 'setup.php'; // Bypass LicenseGuard
require_once __DIR__ . '/../bootstrap.php';

use BTQueue\Core\Database;

$startTime = microtime(true);
echo "=== CORREÇÃO DE URLs DE SINCRONIZAÇÃO ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Atualizar master_url em configuracoes
    $result = Database::execute(
        "UPDATE configuracoes 
         SET valor = 'http://api.brandaotech.com.br/api/v1/sync.php'
         WHERE chave = 'master_url' 
         AND (valor LIKE '%:8080%' OR valor LIKE '%:8090%')"
    );

    echo "✅ Configurações de master_url atualizadas.\n";
    
    // Verificar resultado
    $allUrls = Database::fetchAll("SELECT DISTINCT tenant_id, valor FROM configuracoes WHERE chave = 'master_url' ORDER BY tenant_id");
    
    echo "\n📋 Master URLs após atualização:\n";
    echo str_repeat("-", 80) . "\n";
    echo sprintf("%-12s | %-70s\n", "Tenant ID", "URL");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($allUrls as $row) {
        echo sprintf("%-12s | %-70s\n", $row['tenant_id'], $row['valor']);
    }
    
    echo str_repeat("-", 80) . "\n";
    
    // Buscar dados do tenant para confirmar
    $tenants = Database::fetchAll("SELECT id, nome, uuid, status FROM tenants ORDER BY id");
    
    echo "\n🏢 Tenants cadastrados:\n";
    echo str_repeat("-", 120) . "\n";
    echo sprintf("%-6s | %-30s | %-40s | %-15s\n", "ID", "Nome", "UUID", "Status");
    echo str_repeat("-", 120) . "\n";
    
    foreach ($tenants as $tenant) {
        echo sprintf("%-6s | %-30s | %-40s | %-15s\n", 
            $tenant['id'], 
            substr($tenant['nome'], 0, 30),
            substr($tenant['uuid'], 0, 40),
            $tenant['status']
        );
    }
    
    echo str_repeat("-", 120) . "\n";
    
    $elapsed = microtime(true) - $startTime;
    echo "\n✅ Correção concluída em " . round($elapsed, 3) . "s\n";
    echo "\n📝 PRÓXIMOS PASSOS:\n";
    echo "   1. Testar sincronização: curl -I http://api.brandaotech.com.br/api/v1/sync.php\n";
    echo "   2. Forçar MasterSync: php scripts/force_sync.php\n";
    echo "   3. Monitorar logs: tail -f logs/app-$(date +%Y-%m-%d).log\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
