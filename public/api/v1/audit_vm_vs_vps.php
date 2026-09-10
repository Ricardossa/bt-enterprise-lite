<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain');
echo "--- AUDITORIA VM LOCAL - TENANT 35 (ISRAEL) ---\n";

try {
    $agendamentos = Database::fetch('SELECT COUNT(*) as total FROM senhas WHERE tenant_id = 35 AND status = "AGENDADO" AND DATE(data_agendamento) = CURDATE()');
    echo "Agendamentos para HOJE (Status AGENDADO): " . ($agendamentos['total'] ?? 0) . "\n";

    $presentes = Database::fetch('SELECT COUNT(*) as total FROM senhas WHERE tenant_id = 35 AND status = "PRESENTE" AND DATE(data_agendamento) = CURDATE()');
    echo "Agendamentos que já fizeram Check-in (PRESENTE): " . ($presentes['total'] ?? 0) . "\n\n";

    echo "--- SERVIÇOS E PREÇOS ---\n";
    $servicos = Database::fetchAll('SELECT nome, preco, promo_ativa, promo_desconto FROM servicos WHERE tenant_id = 35 AND ativo = 1');
    foreach($servicos as $s) {
        $promoStatus = ($s['promo_ativa'] == 1) ? "SIM (" . $s['promo_desconto'] . "% OFF)" : "NÃO";
        echo "- " . str_pad($s['nome'], 25) . ": R$ " . str_pad($s['preco'], 8) . " [Promo: $promoStatus]\n";
    }

    echo "\n--- CONFIGURAÇÕES DE SINCRONISMO ---\n";
    $configs = Database::fetchAll('SELECT chave, valor FROM configuracoes WHERE tenant_id = 35 AND chave IN ("sync_token", "master_token", "master_url", "installation_uuid")');
    foreach($configs as $c) {
        echo "- " . str_pad($c['chave'], 20) . ": " . $c['valor'] . "\n";
    }

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
