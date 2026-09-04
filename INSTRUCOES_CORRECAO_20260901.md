# 🔧 INSTRUÇÃO: Atualização de URLs de Sincronização
**Data**: 01/09/2026 17:30  
**Status**: ✅ ARQUIVO PRONTO (MariaDB offline)  
**Prioridade**: 🔴 CRÍTICO

---

## 📝 O QUE FOI FEITO

### Arquivos Atualizados (19 arquivos)
✅ Removidas portas `:8080` e `:8090` de todas as URLs de sincronização

**Aplicações corrigidas**:
- ✅ bt-enterprise-lite (backup, config, HTML)
- ✅ bt-enterprise (config, database, HTML)
- ✅ bt-integration (SyncService, includes)
- ✅ bt-platform (instalacao.php, licenca.php)
- ✅ bt-print (activate.php, setup.php)

### URL Anterior → URL Nova
```
❌ http://api.brandaotech.com.br:8080/api/v1/sync.php
✅ http://api.brandaotech.com.br/api/v1/sync.php

❌ https://api.brandaotech.com.br:8080/...
✅ https://api.brandaotech.com.br/...

❌ http://api.brandaotech.com.br:8080/uploads/...
✅ http://api.brandaotech.com.br/uploads/...
```

---

## 🚀 PRÓXIMAS AÇÕES

### 1️⃣ INICIAR MARIADB (Imediato)
```powershell
# Iniciar o serviço
net start MariaDB

# OU verificar se está rodando
Get-Service MariaDB -ErrorAction SilentlyContinue

# OU iniciar diretamente
Y:\bt-enterprise-lite\runtime\mariadb\bin\mysqld.exe --console
```

### 2️⃣ ATUALIZAR BANCO DE DADOS
Após MariaDB estar online, execute **uma das opções**:

#### **Opção A: Via Script PHP (Recomendado)**
```powershell
cd "y:\bt-enterprise-lite"
& "Y:\bt-enterprise-lite\runtime\php\php.exe" "scripts/update_master_urls_20260901.php"
```

#### **Opção B: Via MySQL CLI (Manual)**
```powershell
# Com user/senha
"Y:\bt-enterprise-lite\runtime\mariadb\bin\mysql.exe" -h 127.0.0.1 -u bt_saas_user -pBrandaoElite2026! bt_enterprise_saas < "y:\bt-enterprise-lite\database\update_master_urls_20260901.sql"

# Ou interativa
"Y:\bt-enterprise-lite\runtime\mariadb\bin\mysql.exe" -h 127.0.0.1 -u bt_saas_user -pBrandaoElite2026! bt_enterprise_saas
mysql> source update_master_urls_20260901.sql;
```

#### **Opção C: Via PHPMyAdmin**
1. Abrir: `http://localhost/phpmyadmin`
2. Banco: `bt_enterprise_saas`
3. Abrir arquivo: `database/update_master_urls_20260901.sql`
4. Executar

---

## ✅ VERIFICAR APÓS ATUALIZAÇÃO

### 1. Validar URLs no Banco
```sql
SELECT tenant_id, chave, valor 
FROM configuracoes 
WHERE chave = 'master_url' 
ORDER BY tenant_id;
```

**Esperado**:
```
tenant_id | chave      | valor
----------|------------|----------------------------------
30        | master_url | http://api.brandaotech.com.br/api/v1/sync.php
(sem mais :8080 ou :8090)
```

### 2. Testar Conectividade com Master
```powershell
# Teste simples (deve retornar 200 OK)
Invoke-WebRequest "http://api.brandaotech.com.br/api/v1/sync.php" -Method POST -Headers @{ "Content-Type"="application/json" } -Body '{"uuid":"test"}' -UseBasicParsing

# Ou com curl
curl -I http://api.brandaotech.com.br/api/v1/sync.php
```

### 3. Forçar Sincronização
```powershell
cd "y:\bt-enterprise-lite"
& "Y:\bt-enterprise-lite\runtime\php\php.exe" "public/diag_sync.php"
```

### 4. Monitorar Logs
```powershell
# Ver último log de aplicação
Get-Content "y:\bt-enterprise-lite\logs\app-2026-09-01.log" -Tail 50

# OU acompanhar em tempo real
Get-Content "y:\bt-enterprise-lite\logs\app-2026-09-01.log" -Tail 10 -Wait
```

---

## 📊 ARQUIVOS MODIFICADOS

### Banco de Dados
- ✅ `database/backup_manual_20260822_161458.sql` - Backup com URL corrigida
- ✅ Arquivo SQL pronto: `database/update_master_urls_20260901.sql`

### Aplicações
- ✅ `bt-enterprise-lite/config/config.php` - Já estava correto
- ✅ `bt-enterprise-lite/temp_zip/` - URLs de imagens
- ✅ `bt-enterprise-lite/_backup_20260825/` - URLs de imagens

### Plataformas
- ✅ `bt-enterprise/config/config.php` - Vazio (não alterado)
- ✅ `bt-enterprise/database/seeds.sql` - Corrigido
- ✅ `bt-platform/public/instalacao.php` - Corrigido
- ✅ `bt-platform/public/licenca.php` - Corrigido
- ✅ `bt-integration/core/MasterSync/SyncService.php` - Corrigido
- ✅ `bt-print/server/public/activate.php` - Corrigido

---

## 🔐 CREDENCIAIS NECESSÁRIAS

Se precisar conectar manualmente ao banco:
```
Host:     127.0.0.1
Usuario:  bt_saas_user
Senha:    BrandaoElite2026!
Banco:    bt_enterprise_saas
Porta:    3306
```

---

## ⚠️ POSSÍVEIS ERROS

### "Connection refused" (Recusa de conexão)
- **Causa**: MariaDB não está rodando
- **Solução**: Iniciar MariaDB com `net start MariaDB`

### "Access denied for user"
- **Causa**: Usuário/senha incorretos
- **Solução**: Verificar credenciais em `config/config.php`

### "Unknown database"
- **Causa**: Banco bt_enterprise_saas não existe
- **Solução**: Restaurar backup: `mysql ... < database/backup_manual_20260822_161458.sql`

### "Syntax error in SQL"
- **Causa**: Arquivo SQL corrompido
- **Solução**: Usar opção PHP em vez de SQL direto

---

## 📋 CHECKLIST PÓS-ATUALIZAÇÃO

- [ ] MariaDB iniciado e rodando
- [ ] Script PHP executado com sucesso
- [ ] Banco de dados atualizado (verificar SELECT)
- [ ] Conectividade testada com `curl`
- [ ] Sincronização forçada
- [ ] Logs revisados (sem erros 404)
- [ ] MARCIO RICARDO funcionando normalmente
- [ ] Documentação atualizada

---

## 📞 REFERÊNCIA

**Instalação SaaS Afetada**:
- UUID: `5579fd18-b48e-473e-a6e1-fb3cf1335700`
- Nome: MARCIO RICARDO (VM BANCADA)
- Status Esperado Após Correção: Sincronização restaurada

**Segunda Instalação**:
- UUID: `4aa0051b-2af1-44a4-a361-ea063c1aaf02`
- Status: NÃO ENCONTRADA localmente (investigar)

---

## 📝 LOGS DE EXECUÇÃO

### Arquivos do Script:
- ✅ `scripts/update_master_urls_20260901.php` - Script de atualização PHP
- ✅ `database/update_master_urls_20260901.sql` - Script SQL manual

### Dados de Execução:
- Data/Hora: 01/09/2026 17:30:55
- Status: Aguardando MariaDB online
- Próximo passo: Executar script PHP após banco disponível

---

**Última atualização**: 01/09/2026 17:35  
**Responsável**: Sistema de Auditoria Automática  
**Status**: ✅ PRONTO PARA EXECUÇÃO
