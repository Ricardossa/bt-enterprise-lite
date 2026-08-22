<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    // 0. Limpeza Geral
    Database::execute("DELETE FROM operadores");
    Database::execute("DELETE FROM licencas");
    Database::execute("DELETE FROM tenants");

    // 1. Cria o Tenant Único com o UUID da sua imagem
    $uuid = '5579fd18-b48e-473e-a6e1-fb3cf1335700';
    $token = '1C3E5CE9B707A09D1A80A33D87C1BF30A5F7D05F68D10E628365A99A52AEF125';
    $slug = 'lite';

    Database::execute(
        "INSERT INTO tenants (uuid, slug, nome, status) VALUES (?, ?, ?, 'ATIVO')",
        [$uuid, $slug, 'VM - BANCADA']
    );

    $tenant = Database::fetch("SELECT id FROM tenants LIMIT 1");
    $tenantId = (int)$tenant['id'];

    // 2. Garante Licença Ativa Local para este Tenant
    Database::execute(
        "INSERT INTO licencas (tenant_id, chave, uuid, token, status, validade) VALUES (?, ?, ?, ?, 'ATIVA', DATE_ADD(NOW(), INTERVAL 1 YEAR))",
        [$tenantId, 'FORCE-KEY-2026', $uuid, $token]
    );

    // 3. Cria Usuário Admin vinculado ao Tenant
    $hash = password_hash('admin', PASSWORD_DEFAULT);
    Database::execute(
        "INSERT INTO operadores (tenant_id, nome, login, senha, nivel, ativo) VALUES (?, ?, ?, ?, 'ADMIN', 1)",
        [$tenantId, 'Administrador Master', 'admin', $hash]
    );

    // 4. Injeta Credenciais de Sincronização (MasterSync)
    $masterUrl = 'http://api.brandaotech.com.br:8080/api/v1/sync.php';
    Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor) VALUES (?, 'master_url', ?)", [$tenantId, $masterUrl]);
    Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor) VALUES (?, 'uuid', ?)", [$tenantId, $uuid]);
    Database::execute("REPLACE INTO configuracoes (tenant_id, chave, valor) VALUES (?, 'token', ?)", [$tenantId, $token]);

    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();

    echo "<h1>✅ LITE SINCRONIZADA COM A MASTER!</h1>";
    echo "<p>Identidade atualizada para: <b>$uuid</b></p>";
    echo "<p>Usuário: <b>admin</b> / Senha: <b>admin</b></p>";
    echo "<br><a href='login.php' style='padding: 15px 30px; background: #18C964; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold;'>ENTRAR NO SISTEMA</a>";
} catch (Exception $e) {
    echo "<h1>❌ ERRO CRÍTICO:</h1> " . $e->getMessage();
}
