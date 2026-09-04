<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

$liteSalt = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'qr_security_salt' AND tenant_id = 1")['valor'] ?? 'brandao_tech_salt_2026';

// Garante que o tenant 1 tenha o salt correto se estiver usando o default
if ($liteSalt === 'default_salt') {
    $liteSalt = 'brandao_tech_salt_2026';
    Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (1, 'qr_security_salt', ?, 'STRING')", [$liteSalt]);
}

// Garante que o tenant 30 tenha o MESMO salt (ou o seu próprio) para validação
// Neste caso, para simplificar e garantir funcionamento, vou replicar o salt padrão para o 30
Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor, tipo) VALUES (30, 'qr_security_salt', ?, 'STRING')", [$liteSalt]);

echo "Salts atualizados para Unidade 1 e Unidade 30: " . $liteSalt;
