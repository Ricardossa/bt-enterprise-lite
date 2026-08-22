-- MIGRATION: BT SCHEDULER v1.0
-- Criação da infraestrutura de agendamento nativo.

CREATE TABLE IF NOT EXISTS agenda_regras (
    id INT PRIMARY KEY AUTO_INCREMENT,
    servico_id INTEGER NOT NULL,
    dia_semana INTEGER NOT NULL,
    hora_inicio TEXT NOT NULL,
    hora_fim TEXT NOT NULL,
    duracao_slot INTEGER DEFAULT 20,
    ativo INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servico_id) REFERENCES servicos(id)
);

CREATE TABLE IF NOT EXISTS agenda_bloqueios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    data DATE NOT NULL,
    hora_inicio TEXT,
    hora_fim TEXT,
    motivo TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
