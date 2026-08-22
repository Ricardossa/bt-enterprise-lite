<?php
/**
 * BT Queue - DIAMOND SECURITY ACTIVATOR (RESCUE)
 * Gera o cadeado digital vinculado ao hardware do cliente.
 */
header('Content-Type: text/plain; charset=utf-8');

use BTQueue\Core\Database;
use BTQueue\Core\DiamondActivationService;

try {
    require_once __DIR__ . '/../bootstrap.php';

    echo "🛡️ INICIANDO VINCULAÇÃO DE HARDWARE DIAMOND...\n\n";

    // Adaptador de suporte: a lógica reside no serviço reutilizável.
    $lic = Database::fetch("SELECT * FROM licencas LIMIT 1");
    if (!$lic) {
        die("❌ ERRO: Nenhuma licença encontrada. O sistema precisa ser ativado via PIN primeiro.");
    }

    $seal = DiamondActivationService::sealLicense((int) $lic['id']);

    echo "\n✅ HARDWARE SELADO: {$seal['hardware_id']}\n";
    echo "✅ ASSINATURA GERADA COM SUCESSO!\n";
    echo "\n🔥 O SISTEMA FOI DESTRAVADO! Pode fazer login no painel agora.";

} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage();
}
?>
