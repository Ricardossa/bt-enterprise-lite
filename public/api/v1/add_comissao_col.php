<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;

try {
    $cols = Database::getTableColumns('operadores');
    if (!in_array('comissao', $cols)) {
        Database::execute("ALTER TABLE operadores ADD COLUMN comissao DECIMAL(5,2) DEFAULT 50.00 AFTER nivel");
        echo "✅ Coluna [comissao] adicionada com sucesso (Padrão 50%).";
    } else {
        echo "ℹ️ A coluna [comissao] já existe.";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
