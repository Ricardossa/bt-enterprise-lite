-- ============================================================
-- BT QUEUE ENTERPRISE - SCHEMA SAAS MULTI-TENANT DEFINITIVO (MARIADB)
-- DATA: 2026-08-17
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. TENANTS (UNIDADES)
CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID auto-gerado localmente ou sincronizado
    uuid VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    status ENUM('ATIVO', 'SUSPENSO', 'BLOQUEADO') DEFAULT 'ATIVO',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. SERVIÇOS
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. GUICHÊS / CADEIRAS
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. VÍNCULO GUICHÊ-SERVIÇO
CREATE TABLE IF NOT EXISTS guiche_servicos (
    guiche_id INT NOT NULL,
    servico_id INT NOT NULL,
    PRIMARY KEY (guiche_id, servico_id),
    CONSTRAINT fk_gs_guiche FOREIGN KEY (guiche_id) REFERENCES guiches(id) ON DELETE CASCADE,
    CONSTRAINT fk_gs_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. OPERADORES / BARBEIROS
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
    magic_token VARCHAR(100),
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_operadores_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY idx_tenant_login (tenant_id, login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. VÍNCULO OPERADOR-SERVIÇO (ESPECIALIDADES)
CREATE TABLE IF NOT EXISTS operador_servicos (
    operador_id INT NOT NULL,
    servico_id INT NOT NULL,
    PRIMARY KEY (operador_id, servico_id),
    CONSTRAINT fk_os_operador FOREIGN KEY (operador_id) REFERENCES operadores(id) ON DELETE CASCADE,
    CONSTRAINT fk_os_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.1 VÍNCULO OPERADOR-GUICHÊ
CREATE TABLE IF NOT EXISTS operador_guiche (
    operador_id INT NOT NULL,
    guiche_id INT NOT NULL,
    PRIMARY KEY (operador_id, guiche_id),
    CONSTRAINT fk_og_operador FOREIGN KEY (operador_id) REFERENCES operadores(id) ON DELETE CASCADE,
    CONSTRAINT fk_og_guiche FOREIGN KEY (guiche_id) REFERENCES guiches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. SENHAS / FILA
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
    tipo_atendimento VARCHAR(20) DEFAULT 'NORMAL',
    data_agendamento DATETIME,
    cancel_token VARCHAR(32),
    pagamento_status VARCHAR(20) DEFAULT 'PENDENTE',
    pagamento_id VARCHAR(100),
    valor_pago DECIMAL(10,2) DEFAULT 0.00,
    valor_total DECIMAL(10,2) DEFAULT 0.00,
    servicos_desc TEXT,
    atendente VARCHAR(255),
    atendente_nome VARCHAR(255),
    emitida_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    chamada_em DATETIME,
    finalizada_em DATETIME,
    sincronizado TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_senhas_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_tenant_created (tenant_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. CONFIGURAÇÕES
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    chave VARCHAR(100) NOT NULL,
    valor TEXT,
    tipo VARCHAR(20) DEFAULT 'STRING',
    descricao TEXT,
    editavel TINYINT(1) DEFAULT 1,
    label_cliente VARCHAR(50) DEFAULT 'Paciente',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_config_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY idx_tenant_chave (tenant_id, chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. REGRAS DE AGENDA
CREATE TABLE IF NOT EXISTS agenda_regras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    operador_id INT NOT NULL,
    servico_id INT,
    dia_semana INT NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    duracao_slot INT DEFAULT 30,
    liberacao_dia_semana INT NULL,
    liberacao_hora_inicio TIME DEFAULT '00:00:00',
    liberacao_hora_fim TIME DEFAULT '23:59:59',
    pausa_inicio TIME NULL,
    pausa_fim TIME NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_agenda_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_agenda_op FOREIGN KEY (operador_id) REFERENCES operadores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. BLOQUEIOS DE AGENDA
CREATE TABLE IF NOT EXISTS agenda_bloqueios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    operador_id INT NOT NULL DEFAULT 0,
    data DATE NOT NULL,
    hora_inicio TIME,
    hora_fim TIME,
    motivo TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bloqueios_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. SUSPENSÕES DE AGENDA
CREATE TABLE IF NOT EXISTS agenda_suspensoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    identificador VARCHAR(100) NOT NULL,
    motivo TEXT,
    data_fim DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_suspensoes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY idx_tenant_identificador (tenant_id, identificador)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. SYNC QUEUE
CREATE TABLE IF NOT EXISTS sync_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    evento VARCHAR(50) NOT NULL,
    entidade VARCHAR(50) NOT NULL,
    referencia_id INT,
    payload JSON,
    prioridade INT DEFAULT 0,
    sincronizado TINYINT(1) DEFAULT 0,
    tentativas INT DEFAULT 0,
    ultimo_erro TEXT,
    processado_em DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sync_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. ATIVIDADES / LOGS
CREATE TABLE IF NOT EXISTS atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    tipo VARCHAR(20) DEFAULT 'INFO',
    nivel VARCHAR(20) DEFAULT 'INFO',
    categoria VARCHAR(50) DEFAULT 'SYSTEM',
    mensagem TEXT NOT NULL,
    usuario VARCHAR(100),
    metadata JSON,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_atividades_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. PROMOÇÕES
CREATE TABLE IF NOT EXISTS promocoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    preco VARCHAR(50),
    imagem TEXT,
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_promocoes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. AUDITORIA
CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    evento VARCHAR(100) NOT NULL,
    usuario VARCHAR(100),
    detalhes TEXT,
    ip VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. SYSTEM INFO
CREATE TABLE IF NOT EXISTS system_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    installation_uuid VARCHAR(100) NOT NULL,
    empresa_uuid VARCHAR(100),
    hostname VARCHAR(255),
    versao VARCHAR(20) NOT NULL,
    build VARCHAR(50),
    ambiente VARCHAR(20) DEFAULT 'PRODUCAO',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_sync DATETIME,
    CONSTRAINT fk_sysinfo_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. LICENÇAS
CREATE TABLE IF NOT EXISTS licencas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    cliente_id INT,
    chave VARCHAR(100) NOT NULL,
    token VARCHAR(100),
    uuid VARCHAR(100),
    status VARCHAR(20) DEFAULT 'ATIVA',
    validade DATETIME,
    ultima_validacao DATETIME,
    offline_dias INT DEFAULT 15,
    assinatura TEXT,
    hardware_id VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_licencas_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY idx_tenant_licenca (tenant_id, chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. CLIENTES
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    uuid VARCHAR(100) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    documento VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clientes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. CONTROLE DE MIGRAÇÕES
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    arquivo VARCHAR(255) UNIQUE,
    checksum VARCHAR(64),
    executado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
