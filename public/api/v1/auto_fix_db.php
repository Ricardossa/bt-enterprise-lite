<?php
/**
 * BT Queue - Silent Auto-Fix Database (DIAMOND v6.4.2)
 * Garante colunas novas, tabelas de agenda e selo de hardware automático.
 */

require_once __DIR__ . '/../../../bootstrap.php';

use BTQueue\Core\Database;
use BTQueue\Core\SecurityService;

// Força o fuso horário para evitar qualquer erro de data
date_default_timezone_set('America/Bahia');

try {
    // 0. GARANTE TABELA DE TENANTS (UNIDADES) - ESSENCIAL PARA SaaS
    Database::execute("
        CREATE TABLE IF NOT EXISTS tenants (
            id INT PRIMARY KEY,
            uuid VARCHAR(100) NOT NULL UNIQUE,
            slug VARCHAR(100) NOT NULL UNIQUE,
            nome VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'ATIVO',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Garante que exista pelo menos um tenant para o sistema não quebrar
    $hasTenant = Database::fetch("SELECT id FROM tenants LIMIT 1");
    if (!$hasTenant) {
        Database::execute("
            INSERT INTO tenants (id, uuid, slug, nome, status)
            VALUES (1, 'default-tenant-uuid', 'lite', 'Unidade Padrão', 'ATIVO')
        ");
    }

    // 1. GARANTE TABELAS DE AGENDAMENTO NATIVO (v5.9.6)
    Database::execute("
        CREATE TABLE IF NOT EXISTS agenda_regras (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            operador_id INTEGER NOT NULL DEFAULT 0,
            servico_id INTEGER NOT NULL,
            dia_semana INTEGER NOT NULL,
            hora_inicio TEXT NOT NULL,
            hora_fim TEXT NOT NULL,
            duracao_slot INTEGER DEFAULT 20,
            ativo INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    Database::execute("
        CREATE TABLE IF NOT EXISTS agenda_bloqueios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            data DATE NOT NULL,
            hora_inicio TEXT,
            hora_fim TEXT,
            motivo TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // 2. GARANTE TABELA DE SUSPENSÕES (v6.4)
    Database::execute("
        CREATE TABLE IF NOT EXISTS agenda_suspensoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            identificador TEXT NOT NULL UNIQUE,
            motivo TEXT,
            data_fim DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // [LITE v3.5.0] MÓDULO DE IDENTIDADE E FIDELIDADE SaaS
    Database::execute("
        CREATE TABLE IF NOT EXISTS clientes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            uuid VARCHAR(64) NOT NULL UNIQUE,
            tenant_id INTEGER NOT NULL,
            nome VARCHAR(255) NOT NULL,
            whatsapp VARCHAR(20) NOT NULL,
            email VARCHAR(255),
            data_nascimento DATE,
            status VARCHAR(20) DEFAULT 'ATIVO',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX(tenant_id),
            INDEX(whatsapp)
        )
    ");

    Database::execute("
        CREATE TABLE IF NOT EXISTS fidelidade_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INTEGER NOT NULL UNIQUE,
            meta_pontos INTEGER DEFAULT 10,
            premio_desc VARCHAR(255) DEFAULT 'Corte Grátis',
            ativo TINYINT(1) DEFAULT 1,
            INDEX(tenant_id)
        )
    ");

    Database::execute("
        CREATE TABLE IF NOT EXISTS fidelidade_saldo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INTEGER NOT NULL,
            cliente_id INTEGER NOT NULL,
            saldo_pontos INTEGER DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE(tenant_id, cliente_id),
            INDEX(tenant_id),
            INDEX(cliente_id)
        )
    ");

    Database::execute("
        CREATE TABLE IF NOT EXISTS fidelidade_historico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INTEGER NOT NULL,
            cliente_id INTEGER NOT NULL,
            tipo ENUM('CREDITO', 'DEBITO') NOT NULL,
            pontos INTEGER NOT NULL,
            descricao VARCHAR(255),
            referencia_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX(tenant_id),
            INDEX(cliente_id)
        )
    ");

    // [LITE v3.5.9] GARANTE TABELA DE ATIVIDADES SaaS
    Database::execute("
        CREATE TABLE IF NOT EXISTS atividades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            tipo VARCHAR(50) DEFAULT 'INFO',
            categoria VARCHAR(50) DEFAULT 'SYSTEM',
            mensagem TEXT NOT NULL,
            usuario VARCHAR(100) NULL,
            metadata TEXT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_atividades_tenant (tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 3. GARANTE CONFIGURAÇÕES DE WHATSAPP (v6.6)
    $tenantId = \BTQueue\Core\Auth::tenantId();
    $waConfigs = [
        [$tenantId, 'whatsapp_enabled', '0', 'BOOLEAN', 'Habilita notificações automáticas via WhatsApp'],
        [$tenantId, 'whatsapp_api_url', '', 'STRING', 'URL da Instância da API (Ex: Evolution API)'],
        [$tenantId, 'whatsapp_api_token', '', 'STRING', 'Token de autenticação da API'],
        [$tenantId, 'ai_url', 'http://192.168.100.250:11434/api/generate', 'STRING', 'URL do motor de Inteligência Artificial (Ollama)'],
        [$tenantId, 'radar_enabled', '0', 'BOOLEAN', 'Habilita o radar de faltas automáticas'],
        [$tenantId, 'radar_tolerance', '15', 'NUMBER', 'Minutos de tolerância para falta automática'],
        [$tenantId, 'priority_mode', 'STRICT', 'STRING', 'Modo de chamada: STRICT (Sempre Prioridade) ou BALANCED (Intercalado)'],
        [$tenantId, 'priority_ratio', '3', 'NUMBER', 'Quantidade de prioridades antes de um normal (no modo BALANCED)'],
        [$tenantId, 'feature_priority_selection', '1', 'BOOLEAN', 'Habilita a tela de escolha entre Normal e Prioritário no Totem']
    ];

    if ($tenantId > 0) {
        foreach ($waConfigs as $c) {
            Database::execute(
                "REPLACE INTO configuracoes (tenant_id, chave, valor, tipo, descricao) VALUES (?, ?, ?, ?, ?)",
                $c
            );
        }
    }

    // 4. GARANTE COLUNAS NOVAS EM TABELAS EXISTENTES
    $migrations = [
        'clientes' => [
            'uuid' => 'VARCHAR(64) NOT NULL UNIQUE',
            'tenant_id' => 'INTEGER NOT NULL',
            'nome' => 'VARCHAR(255) NOT NULL',
            'whatsapp' => 'VARCHAR(20) NOT NULL',
            'email' => 'VARCHAR(255)',
            'data_nascimento' => 'DATE',
            'status' => "VARCHAR(20) DEFAULT 'ATIVO'",
            'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ],
        'senhas' => [
            'tenant_id' => 'INTEGER DEFAULT 1',
            'cliente_id' => 'INTEGER DEFAULT 0',
            'nome_cliente' => 'TEXT',
            'atendente_nome' => 'TEXT',
            'tipo_atendimento' => "TEXT DEFAULT 'NORMAL'",
            'valor_total' => 'DECIMAL(10,2) DEFAULT 0.00',
            'pagamento_status' => "VARCHAR(20) DEFAULT 'PENDENTE'",
            'data_agendamento' => 'DATETIME',
            'emitida_em' => 'DATETIME',
            'whatsapp' => 'TEXT',
            'cancel_token' => 'TEXT',
            'pagamento_id' => 'VARCHAR(100)',
            'pix_qr_code' => 'TEXT',
            'pix_qr_base64' => 'LONGTEXT'
        ],
        'operadores' => [
            'tenant_id' => 'INTEGER DEFAULT 1',
            'prefixo' => 'VARCHAR(5)',
            'status' => "VARCHAR(20) DEFAULT 'ONLINE'"
        ],
        'servicos' => [
            'tenant_id' => 'INTEGER DEFAULT 1'
        ],
        'guiches' => [
            'tenant_id' => 'INTEGER DEFAULT 1'
        ],
        'promocoes' => [
            'tenant_id' => 'INTEGER DEFAULT 1'
        ],
        'configuracoes' => [
            'tenant_id' => 'INTEGER DEFAULT 1',
            'label_cliente' => "TEXT DEFAULT 'Paciente'"
        ],
        'licencas' => [
            'tenant_id' => 'INTEGER DEFAULT 1',
            'assinatura' => 'TEXT',
            'hardware_id' => 'TEXT'
        ],
        'agenda_regras' => [
            'tenant_id' => 'INTEGER DEFAULT 1',
            'operador_id' => 'INTEGER DEFAULT 0',
            'liberacao_dia_semana' => 'INTEGER NULL',
            'liberacao_hora_inicio' => "TEXT DEFAULT '00:00'",
            'liberacao_hora_fim' => "TEXT DEFAULT '23:59'"
        ],
        'system_info' => [
            'tenant_id' => 'INTEGER DEFAULT 1',
            'installation_uuid' => 'VARCHAR(255) NOT NULL UNIQUE',
            'empresa_uuid' => 'VARCHAR(255)',
            'hostname' => 'VARCHAR(255)',
            'versao' => 'VARCHAR(50) NOT NULL',
            'build' => 'VARCHAR(50)',
            'schema_version' => 'INTEGER DEFAULT 2',
            'ambiente' => "VARCHAR(50) DEFAULT 'PRODUCAO'",
            'php_version' => 'VARCHAR(50)',
            'mariadb_version' => 'VARCHAR(50)',
            'last_sync' => 'DATETIME',
            'last_heartbeat' => 'DATETIME'
        ]
    ];

    foreach ($migrations as $table => $columns) {
        // [LITE v3.3.4] Tenta buscar colunas de forma mais robusta (SHOW COLUMNS)
        try {
            $stmt = Database::getInstance()->query("SHOW COLUMNS FROM $table");
            $existingCols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        } catch (\Throwable $e) {
            $existingCols = [];
        }

        if (empty($existingCols)) continue;

        foreach ($columns as $col => $type) {
            if (!in_array($col, $existingCols)) {
                try {
                    Database::execute("ALTER TABLE $table ADD COLUMN $col $type");
                    echo "✅ Coluna [$col] adicionada em [$table]<br>";
                } catch (Exception $e) {
                    echo "❌ Erro ao adicionar [$col] em [$table]: " . $e->getMessage() . "<br>";
                }
            }
        }
    }

    // 3. CURA DE TEMPO (v6.8.6: Remove horários do futuro que travam a TV)
    Database::execute("
        UPDATE senhas
        SET chamada_em = NOW()
        WHERE status IN ('CHAMANDO', 'FINALIZADA')
        AND DATE(created_at) = CURDATE()
        AND chamada_em > DATE_ADD(NOW(), INTERVAL 5 MINUTE)
    ");

    // 4. AUTO-SELO DE HARDWARE (MIGRAÇÃO DE SEGURANÇA)
    $lic = Database::fetch("SELECT * FROM licencas LIMIT 1");
    if ($lic && empty($lic['assinatura']) && !empty($lic['token']) && class_exists('BTQueue\Core\SecurityService')) {
        $hwid = SecurityService::getHardwareId();
        $sig = SecurityService::signData([
            'uuid' => (string)($lic['uuid'] ?? ''),
            'status' => (string)($lic['status'] ?? ''),
            'validade' => (string)($lic['validade'] ?? '')
        ], (string)$lic['token']);

        Database::execute(
            "UPDATE licencas SET hardware_id = ?, assinatura = ? WHERE id = ?",
            [$hwid, $sig, $lic['id']]
        );
    }

} catch (Exception $e) {
    if (class_exists('BTQueue\Core\Logger')) {
        \BTQueue\Core\Logger::error("Auto-Fix DB Error: " . $e->getMessage());
    }
}
?>
