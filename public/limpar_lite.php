<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    // 1. Limpa as tabelas de identidade
    Database::execute("DELETE FROM licencas");
    Database::execute("DELETE FROM tenants");
    Database::execute("DELETE FROM configuracoes WHERE chave IN ('uuid', 'token', 'master_url')");
    Database::execute("DELETE FROM operadores WHERE nivel = 'ADMIN'"); // Opcional: para recriar o admin no passo 4

    // 2. Remove a trava de instalação se existir
    $lockFile = dirname(__DIR__) . '/database/.installed';
    if (file_exists($lockFile)) unlink($lockFile);

    echo "<h1>✅ LITE LIMPA COM SUCESSO!</h1>";
    echo "<p>As tabelas de licença e identidade foram zeradas.</p>";
    echo "<a href='setup.php?step=3' style='padding: 10px 20px; background: #18C964; color: #fff; text-decoration: none; border-radius: 5px;'>IR PARA O PASSO 3 (ATIVAR)</a>";
} catch (Exception $e) {
    echo "<h1>❌ ERRO AO LIMPAR:</h1> " . $e->getMessage();
}
