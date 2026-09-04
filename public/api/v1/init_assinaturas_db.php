<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;

try {
    echo "Iniciando criação do módulo de Assinaturas...\n";

    // 1. Tabela de Planos do Clube (Criado pelo Barbeiro)
    Database::execute("
        CREATE TABLE IF NOT EXISTS clube_planos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            nome VARCHAR(100) NOT NULL,
            preco DECIMAL(10,2) NOT NULL,
            qtd_cortes INT DEFAULT 4,
            validade_dias INT DEFAULT 30,
            descricao TEXT,
            ativo TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 2. Tabela de Assinaturas Ativas (Vínculo Cliente x Plano)
    Database::execute("
        CREATE TABLE IF NOT EXISTS clube_assinaturas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            cliente_id INT NOT NULL,
            plano_id INT NOT NULL,
            cortes_restantes INT NOT NULL,
            data_inicio DATE NULL DEFAULT NULL,
            data_fim DATE NULL DEFAULT NULL,
            status ENUM('ATIVA', 'EXPIRADA', 'CANCELADA', 'PENDENTE') DEFAULT 'PENDENTE',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX(cliente_id),
            INDEX(tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "✅ Módulo de Assinaturas (SaaS) criado com sucesso!";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
