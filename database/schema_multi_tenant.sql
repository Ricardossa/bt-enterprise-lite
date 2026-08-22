-- ============================================================
-- BT QUEUE ENTERPRISE - NOVO SCHEMA SAAS MULTI-TENANT (MARIADB)
-- ============================================================

-- 1. ENTIDADE MASTER DE TENANTS (Vem da Platform Master)
CREATE TABLE IF NOT EXISTS tenants (
    id INT PRIMARY KEY, -- ID exato da instalacao na Master
    uuid VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    status VARCHAR(20) DEFAULT 'ATIVO',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. SERVIÇOS (Isolados por Tenant)
CREATE TABLE IF NOT EXISTS servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    prefixo VARCHAR(5) NOT NULL,
    icone VARCHAR(10) DEFAULT '📋',
    cor VARCHAR(7) DEFAULT '#1565C0',
    ordem INT DEFAULT 0,
    tempo_medio INT DEFAULT 10,
    preco DECIMAL(10,2) DEFAULT 0.00,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_servicos_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY idx_tenant_servico_slug (tenant_id, slug),
    UNIQUE KEY idx_tenant_servico_codigo (tenant_id, codigo)
);

-- 3. GUICHÊS / CADEIRAS (Isolados por Tenant)
CREATE TABLE IF NOT EXISTS guiches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    icone VARCHAR(10) DEFAULT '⚙️',
    cor VARCHAR(7) DEFAULT '#1565C0',
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_guiches_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY idx_tenant_guiche_codigo (tenant_id, codigo)
);

-- 4. VÍNCULO GUICHÊ-SERVIÇO
CREATE TABLE IF NOT EXISTS guiche_servicos (
    guiche_id INT NOT NULL,
    servico_id INT NOT NULL,
    PRIMARY KEY (guiche_id, servico_id),
    CONSTRAINT fk_gs_guiche FOREIGN KEY (guiche_id) REFERENCES guiches(id) ON DELETE CASCADE,
    CONSTRAINT fk_gs_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE
);

-- 5. OPERADORES / BARBEIROS (Isolados por Tenant)
CREATE TABLE IF NOT EXISTS operadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    login VARCHAR(100) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('ADMIN', 'GERENTE', 'OPERADOR') DEFAULT 'OPERADOR',
    guiche_id INT,
    servico_id INT,
    prefixo VARCHAR(5),
    foto_url TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_operadores_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_operadores_guiche FOREIGN KEY (guiche_id) REFERENCES guiches(id) ON SET NULL,
    UNIQUE KEY idx_tenant_login (tenant_id, login)
);

-- 6. SENHAS / FILA (Isolado e Indexado)
CREATE TABLE IF NOT EXISTS senhas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    uuid VARCHAR(64) NOT NULL UNIQUE,
    cliente_uuid VARCHAR(64),
    servico_id INT,
    guiche_id INT,
    operador_id INT,
    device_id VARCHAR(100),
    codigo VARCHAR(20) NOT NULL,
    numero INT NOT NULL,
    prefixo VARCHAR(5) NOT NULL,
    nome_cliente VARCHAR(255),
    whatsapp VARCHAR(20),
    status VARCHAR(20) DEFAULT 'AGUARDANDO',
    data_agendamento DATETIME,
    cancel_token VARCHAR(32),
    pagamento_status VARCHAR(20) DEFAULT 'PENDENTE',
    pagamento_id VARCHAR(100),
    valor_total DECIMAL(10,2) DEFAULT 0.00,
    servicos_desc TEXT,
    emitida_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    chamada_em DATETIME,
    finalizada_em DATETIME,
    sincronizado TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_senhas_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_senhas_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON SET NULL,
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_tenant_data (tenant_id, created_at)
);

-- 7. CONFIGURAÇÕES (Por Tenant)
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    chave VARCHAR(100) NOT NULL,
    valor TEXT,
    tipo VARCHAR(20) DEFAULT 'STRING',
    descricao TEXT,
    CONSTRAINT fk_config_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY idx_tenant_chave (tenant_id, chave)
);

-- 8. REGRAS DE AGENDA
CREATE TABLE IF NOT EXISTS agenda_regras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    operador_id INT NOT NULL,
    servico_id INT,
    dia_semana INT NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    duracao_slot INT DEFAULT 30,
    liberacao_dia_semana INT,
    liberacao_hora_inicio TIME DEFAULT '00:00:00',
    liberacao_hora_fim TIME DEFAULT '23:59:59',
    ativo TINYINT(1) DEFAULT 1,
    CONSTRAINT fk_agenda_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_agenda_op FOREIGN KEY (operador_id) REFERENCES operadores(id) ON DELETE CASCADE
);

-- 9. ATIVIDADES / LOGS
CREATE TABLE IF NOT EXISTS atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    tipo VARCHAR(20) DEFAULT 'INFO',
    categoria VARCHAR(50) DEFAULT 'SYSTEM',
    mensagem TEXT NOT NULL,
    usuario VARCHAR(100),
    metadata JSON,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_atividades_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_log (tenant_id, data_criacao)
);
