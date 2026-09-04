-- Script de Atualização: Remover portas 8080/8090 das URLs de sincronização
-- Data: 01/09/2026
-- Banco de dados: bt_enterprise_saas
-- Descrição: Atualiza todas as master_url para usar http://api.brandaotech.com.br (porta 80 padrão)

-- ============================================================================
-- PARTE 1: Atualizar master_url na tabela configuracoes
-- ============================================================================

-- Backup: Ver URLs antigas antes de atualizar
SELECT 'BACKUP - ANTES' as operacao, tenant_id, valor 
FROM configuracoes 
WHERE chave = 'master_url' 
ORDER BY tenant_id;

-- Atualizar: Remover :8080 e :8090
UPDATE configuracoes 
SET valor = REPLACE(REPLACE(valor, ':8080', ''), ':8090', '')
WHERE chave = 'master_url' 
AND (valor LIKE '%:8080%' OR valor LIKE '%:8090%');

-- Verificação: Confirmar URLs após atualização
SELECT 'CONFIRMAÇÃO - DEPOIS' as operacao, tenant_id, valor 
FROM configuracoes 
WHERE chave = 'master_url' 
ORDER BY tenant_id;

-- ============================================================================
-- PARTE 2: Listar todos os tenants e seus status de sincronização
-- ============================================================================

SELECT 
    t.id,
    t.nome,
    t.uuid,
    t.status,
    c1.valor as master_url,
    c2.valor as token,
    c3.valor as uuid_config,
    l.status as license_status,
    l.validade as license_expiry
FROM tenants t
LEFT JOIN configuracoes c1 ON t.id = c1.tenant_id AND c1.chave = 'master_url'
LEFT JOIN configuracoes c2 ON t.id = c2.tenant_id AND c2.chave = 'token'
LEFT JOIN configuracoes c3 ON t.id = c3.tenant_id AND c3.chave = 'uuid'
LEFT JOIN licencas l ON t.id = l.tenant_id
ORDER BY t.id;

-- ============================================================================
-- PARTE 3: Registrar no log de auditoria
-- ============================================================================

-- Nota: Este arquivo deve ser executado via:
-- mysql -h 127.0.0.1 -u bt_saas_user -pBrandaoElite2026! bt_enterprise_saas < update_master_urls.sql

-- Ou via aplicação PHP usando Database::execute()

-- ============================================================================
-- RESULTADO ESPERADO:
-- ============================================================================
-- master_url anterior: http://api.brandaotech.com.br:8080/api/v1/sync.php
-- master_url novo:    http://api.brandaotech.com.br/api/v1/sync.php
--
-- Portas removidas:
-- :8080 -> (removido)
-- :8090 -> (removido)
--
-- Protocolo: HTTP padrão (porta 80)
-- ============================================================================
