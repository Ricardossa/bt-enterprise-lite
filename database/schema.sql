-- ============================================================
-- BT QUEUE ENTERPRISE - SCHEMA OFICIAL V5.0 MARIADB (NON-DESTRUCTIVE)
-- ============================================================

-- 0. TENANTS (UNIDADES)
CREATE TABLE IF NOT EXISTS tenants (
    id INT PRIMARY KEY,
    uuid VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    status VARCHAR(20) DEFAULT 'ATIVO',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 1. IDENTIDADE DA INSTALAÇÃO
CREATE TABLE IF NOT EXISTS system_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT DEFAULT 1,
    installation_uuid VARCHAR(255) NOT NULL UNIQUE,
    empresa_uuid VARCHAR(255),
    hostname VARCHAR(255),
    versao VARCHAR(50) NOT NULL,
    build VARCHAR(50),
    schema_version INT DEFAULT 2,
    ambiente VARCHAR(50) DEFAULT 'PRODUCAO',
    php_version VARCHAR(50),
    mariadb_version VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_sync DATETIME,
    last_heartbeat DATETIME,
    FOREIGN KEY(tenant_id) REFERENCES tenants(id)
);

-- 2. CLIENTES
CREATE TABLE IF NOT EXISTS clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    whatsapp VARCHAR(20),
    email VARCHAR(255),
    documento VARCHAR(50),
    status VARCHAR(20) DEFAULT 'ATIVO',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(tenant_id) REFERENCES tenants(id)
);

-- 3. LICENÇAS
CREATE TABLE IF NOT EXISTS licencas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    cliente_id INT NOT NULL,
    chave VARCHAR(255) NOT NULL UNIQUE,
    token TEXT,
    uuid VARCHAR(255),
    status VARCHAR(50) NOT NULL DEFAULT 'ATIVA',
    validade DATETIME,
    ultima_validacao DATETIME,
    cache_assinatura TEXT,
    hardware_id VARCHAR(255),
    assinatura TEXT,
    offline_dias INT DEFAULT 7,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(cliente_id) REFERENCES clientes(id),
    FOREIGN KEY(tenant_id) REFERENCES tenants(id)
);

-- 4. SERVIÇOS
CREATE TABLE IF NOT EXISTS servicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    prefixo VARCHAR(10) NOT NULL,
    icone VARCHAR(50) DEFAULT '📋',
    cor VARCHAR(20) DEFAULT '#1565C0',
    ordem INT DEFAULT 0,
    tempo_medio INT DEFAULT 10,
    ativo TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(tenant_id) REFERENCES tenants(id)
);

-- 5. GUICHÊS
CREATE TABLE IF NOT EXISTS guiches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    icone VARCHAR(50) DEFAULT '⚙️',
    cor VARCHAR(20) DEFAULT '#1565C0',
    ativo TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(tenant_id) REFERENCES tenants(id)
);

-- 6. VÍNCULO GUICHÊ-SERVIÇO
CREATE TABLE IF NOT EXISTS guiche_servicos (
    guiche_id INT NOT NULL,
    servico_id INT NOT NULL,
    PRIMARY KEY (guiche_id, servico_id),
    FOREIGN KEY(guiche_id) REFERENCES guiches(id) ON DELETE CASCADE,
    FOREIGN KEY(servico_id) REFERENCES servicos(id) ON DELETE CASCADE
);

-- 7. SENHAS
CREATE TABLE IF NOT EXISTS senhas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    tenant_id INT NOT NULL,
    cliente_id INT DEFAULT 0,
    cliente_uuid VARCHAR(255) NOT NULL,
    servico_id INT,
    guiche_id INT,
    device_id VARCHAR(255),
    codigo VARCHAR(50) NOT NULL,
    numero INT NOT NULL,
    prefixo VARCHAR(10) NOT NULL,
    atendente VARCHAR(255),
    status VARCHAR(50) NOT NULL DEFAULT 'AGUARDANDO',
    valor_total DECIMAL(10,2) DEFAULT 0.00,
    pagamento_status VARCHAR(20) DEFAULT 'PENDENTE',
    emitida_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    chamada_em DATETIME,
    finalizada_em DATETIME,
    sincronizado TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(servico_id) REFERENCES servicos(id),
    FOREIGN KEY(guiche_id) REFERENCES guiches(id),
    FOREIGN KEY(tenant_id) REFERENCES tenants(id),
    INDEX(tenant_id),
    INDEX(cliente_id)
);

-- 8. OPERADORES
CREATE TABLE IF NOT EXISTS operadores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    login VARCHAR(255) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    nivel VARCHAR(50) DEFAULT 'OPERADOR',
    guiche_id INT,
    servico_id INT,
    ativo TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(guiche_id) REFERENCES guiches(id),
    FOREIGN KEY(servico_id) REFERENCES servicos(id),
    FOREIGN KEY(tenant_id) REFERENCES tenants(id),
    UNIQUE KEY idx_tenant_login (tenant_id, login)
);

-- 9. CONFIGURAÇÕES
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    chave VARCHAR(255) NOT NULL,
    valor TEXT,
    tipo VARCHAR(50) DEFAULT 'STRING',
    descricao TEXT,
    editavel TINYINT DEFAULT 1,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(tenant_id) REFERENCES tenants(id),
    UNIQUE KEY idx_tenant_chave (tenant_id, chave)
);

-- 10. SYNC QUEUE
CREATE TABLE IF NOT EXISTS sync_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    evento VARCHAR(255) NOT NULL,
    entidade VARCHAR(255) NOT NULL,
    referencia_id INT,
    payload LONGTEXT,
    prioridade INT DEFAULT 0,
    sincronizado TINYINT DEFAULT 0,
    tentativas INT DEFAULT 0,
    ultimo_erro TEXT,
    processado_em DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(tenant_id) REFERENCES tenants(id)
);

-- 11. ATIVIDADES
CREATE TABLE IF NOT EXISTS atividades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    tipo VARCHAR(50) DEFAULT 'INFO',
    nivel VARCHAR(50) DEFAULT 'INFO',
    categoria VARCHAR(50) DEFAULT 'SYSTEM',
    mensagem TEXT NOT NULL,
    usuario VARCHAR(255) NULL,
    metadata LONGTEXT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(tenant_id) REFERENCES tenants(id)
);

-- 12. PROMOÇÕES
CREATE TABLE IF NOT EXISTS promocoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    preco VARCHAR(50),
    imagem TEXT,
    ordem INT DEFAULT 0,
    ativo TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(tenant_id) REFERENCES tenants(id)
);

-- 13. CONTROLE DE MIGRAÇÕES
CREATE TABLE IF NOT EXISTS migrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    arquivo VARCHAR(255) UNIQUE,
    checksum VARCHAR(255),
    executado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ÍNDICES (Criados com tratamento de erro silencioso no MariaDB)
-- No MariaDB para rodar via script sem falhar se já existir usamos:
-- DROP INDEX IF EXISTS idx_senhas_status ON senhas;
-- Mas para ser 100% compatível e não-destrutivo:
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_NAME = 'senhas' AND INDEX_NAME = 'idx_senhas_status' AND TABLE_SCHEMA = DATABASE()
    ) > 0,
    "SELECT 1",
    "CREATE INDEX idx_senhas_status ON senhas(status)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_NAME = 'senhas' AND INDEX_NAME = 'idx_senhas_uuid' AND TABLE_SCHEMA = DATABASE()
    ) > 0,
    "SELECT 1",
    "CREATE INDEX idx_senhas_uuid ON senhas(uuid)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_NAME = 'sync_queue' AND INDEX_NAME = 'idx_sync_status' AND TABLE_SCHEMA = DATABASE()
    ) > 0,
    "SELECT 1",
    "CREATE INDEX idx_sync_status ON sync_queue(sincronizado)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_NAME = 'atividades' AND INDEX_NAME = 'idx_atividades_data' AND TABLE_SCHEMA = DATABASE()
    ) > 0,
    "SELECT 1",
    "CREATE INDEX idx_atividades_data ON atividades(data_criacao)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
