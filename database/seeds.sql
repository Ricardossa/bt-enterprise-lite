-- ============================================================
-- BT QUEUE ENTERPRISE - DADOS INICIAIS (SEEDS V4.2)
-- ============================================================

-- 0. TENANT PADRÃO (Desativado em modo SaaS VPS v8.5)
-- INSERT IGNORE INTO tenants (id, uuid, slug, nome, status) VALUES
-- (1, 'default-tenant-uuid', 'lite', 'Unidade Padrão', 'ATIVO');

-- 1. CONFIGURAÇÕES PADRÃO (Injetadas via provisionTenant dinamicamente)
INSERT IGNORE INTO configuracoes (tenant_id, chave, valor, tipo, descricao) VALUES
(1, 'master_url', 'http://api.brandaotech.com.br/api/v1/sync.php', 'STRING', 'URL de sincronização com a Platform Master'),
(1, 'app_name', 'BT Queue Enterprise', 'STRING', 'Nome da aplicação local'),
(1, 'offline_limit_days', '7', 'INT', 'Dias permitidos de operação sem sincronização');

-- 2. SERVIÇO PADRÃO
INSERT IGNORE INTO servicos (tenant_id, codigo, nome, slug, prefixo, icone, cor, ordem) VALUES
(1, '1', 'Atendimento Geral', 'atendimento-geral', 'A', '📋', '#1565C0', 1);

-- 3. GUICHÊ PADRÃO
INSERT IGNORE INTO guiches (tenant_id, codigo, nome, icone, cor) VALUES
(1, '01', 'Mesa 01', '⚙️', '#1565C0');

-- 4. VÍNCULO INICIAL
INSERT IGNORE INTO guiche_servicos (guiche_id, servico_id) VALUES (1, 1);
