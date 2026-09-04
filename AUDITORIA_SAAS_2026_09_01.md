# RELATÓRIO DE AUDITORIA SAAS
## BT Queue Enterprise Lite - Duas Instalações
**Data**: 01/09/2026 às 16:40 (UTC-3)  
**Escopo**: Auditoria sem alterações  
**Status**: ⚠️ DOIS PROBLEMAS CRÍTICOS IDENTIFICADOS

---

## RESUMO EXECUTIVO

Duas instalações SaaS foram reportadas como OFFLINE:

| Campo | MARCIO RICARDO (VM BANCADA) | Parada Obrigatória Vilas |
|-------|-------|-------|
| **UUID** | `5579fd18-b48e-473e-a6e1-fb3cf1335700` | `4aa0051b-2af1-44a4-a361-ea063c1aaf02` |
| **Produto** | BT_QUEUE_ENTERPRISE_LITE | BT_QUEUE_ENTERPRISE_LITE |
| **Versão** | v4.0.0 | v1.0.0 |
| **Status Reportado** | OFFLINE | OFFLINE |
| **Últim Sincronização** | 01/09 16:33 | Nunca |
| **Status no BD** | ATIVO ✅ | NÃO ENCONTRADO ❌ |
| **Licença** | ATIVA (até 2027-08-22) | DESCONHECIDA |

---

## INSTALAÇÃO 1: MARCIO RICARDO (VM BANCADA) - UUID 5579fd18-b48e-473e-a6e1-fb3cf1335700

### ✅ Status no Banco de Dados
```
Tenant ID: 30
Nome: "VM - BANCADA"
Slug Atual: "lite" (renomeado para "paradaobrigatoriavilas" em 29/08)
Status: ATIVO
Criado: 22/08/2026 09:49:19
```

### 🔧 Configuração de Sincronização
| Parâmetro | Valor |
|-----------|-------|
| Master URL | `http://api.brandaotech.com.br:8080/api/v1/sync.php` |
| Token | `1C3E5CE9B707A09D1A80A33D87C1BF30A5F7D05F68D10E628365A99A52AEF125` |
| Modo | `cloud` |
| URL Local | `http://192.168.100.245` |
| URL Pública | `http://lite.brandaotech.com.br:8120` |
| IP Impressora | `192.168.100.126` |
| Offline Permitido | 7 dias |

### 📋 Dados Operacionais
**Serviços Cadastrados**: 6
- Corte Simples (R$ 30,00)
- Degradê/Taper Fade (R$ 70,00)
- Barba (R$ 15,00)
- Corte + Barba (R$ 35,00)
- Vacinação (R$ 0,00 - DESATIVADO)
- Exames (R$ 0,00 - DESATIVADO)

**Guichês**: 38, 39, 40 (3 guichês operacionais)

**Operadores**: 4
- Admin Master (ID 30)
- Ricardo Brandão (ID 31)
- JOÃO (ID 33)
- PIU (ID 34)

**Tickets/Senhas**: 3 registros
- ID 84: MARCIO RICARDO BRANDÃO DE JESUS - Degradê - FINALIZADA (22/08 11:12:28)
- ID 85: Anônimo - Degradê - FINALIZADA (22/08 13:05:23)
- ID 86: RICARDO - Corte + Barba - FINALIZADA (22/08 13:12:22)

### 📡 Histórico de Sincronização
```
SUCESSO (últimas datas):
✅ 30/08/2026 13:37:57 - ID: d459d0da
✅ 30/08/2026 13:23:37 - ID: 32cd08fe
✅ 30/08/2026 13:18:12 - ID: 6a6dd691
✅ 30/08/2026 13:17:51 - ID: 4b875e96

FALHA (persistente desde 26/07):
❌ 26/07/2026 19:53:57 - HTTP 404 do Platform Master
❌ 26/07/2026 19:54:02 - HTTP 404 do Platform Master
... (múltiplas tentativas falhadas)

ÚLTIMO ERRO:
❌ 02/08/2026 09:26:21 - Timeout após 10002ms
```

### 📊 Erros Recentes do Banco de Dados
```
[2026-09-01 08:25:28] ERRO SQL - SQLSTATE[42000] em search_8080.php:12
    "Syntax error... near 'text' at line 1"

[2026-09-01 13:36:43] ERRO - SQLSTATE[40001] Deadlock
    "Deadlock found when trying to get lock; try restarting transaction"
    (3 ocorrências em 4 segundos)
```

### 🔐 Informações de Licença
| Campo | Valor |
|-------|-------|
| Status | **ATIVA** ✅ |
| Chave | `FORCE-KEY-2026` |
| Válida até | **22/08/2027** |
| Hardware ID | `174d0c49541eb26014b5f39f1286a04c9f63ba53b396de5b79d5a6522d81a234` |
| Token Segurança | `1C3E5CE9B707A09D1A80A33D87C1BF30A5F7D05F68D10E628365A99A52AEF125` |
| Offline Permitido | 15 dias |
| Última Validação | NULL (nunca foi validada online) |

---

## INSTALAÇÃO 2: PARADA OBRIGATÓRIA VILAS PREMIUM - UUID 4aa0051b-2af1-44a4-a361-ea063c1aaf02

### ❌ Status CRÍTICO: NÃO ENCONTRADA NO BANCO DE DADOS

**Resultado da Busca**:
```
Banco: bt_enterprise_lite
Tabela: tenants
Busca por UUID: 4aa0051b-2af1-44a4-a361-ea063c1aaf02
Resultado: 0 registros encontrados

Arquivo de investigação: investigate_issue.php (sem resultados)
Log de auditoria: Sem menção específica em db_audit.log (29/08)
```

### ⚠️ Observações Críticas
1. **Não existe instalação local** correspondente a este UUID no banco de dados `bt_enterprise_lite`
2. **Versão reportada é v1.0.0** (mais antiga que v4.0.0 do MARCIO RICARDO)
3. **Sincronização nunca ocorreu** - não há registros em logs
4. **Licença desconhecida** - não há informações sobre validade ou token

### 🔍 Possíveis Cenários
| Cenário | Evidência | Probabilidade |
|---------|-----------|---------------|
| Instalação em servidor diferente | UUID não existe em bt_enterprise_lite | ⭐⭐⭐⭐⭐ ALTA |
| Banco de dados não sincronizado | Deve estar em outro servidor/VM | ⭐⭐⭐⭐ ALTA |
| Licença suspensa/desativada | Não aparece no dashboard | ⭐⭐⭐ MÉDIA |
| UUID inválido/desatualizado | Reportado inconsistentemente | ⭐⭐ BAIXA |

---

## ANÁLISE DE PROBLEMAS

### 🔴 Problema 1: Sincronização Quebrada desde 26/07/2026

**Sintomas**:
- HTTP 404 do Platform Master a partir de 26/07 19:53:57
- Timeout de conexão em 02/08 09:26:21
- Ambas as instalações OFFLINE apesar de última sincronização reportada

**Origem Provável**:
```
Master URL: http://api.brandaotech.com.br:8080/api/v1/sync.php
Erro: 404 Not Found (page HTML retornada)
```
Endpoint de sincronização pode estar indisponível ou URL pode estar incorreta.

**Impacto**:
- ❌ Impossível sincronizar dados com o master
- ❌ Status de licença não é validado
- ⚠️ Sistema pode operar offline por até 7 dias (configurado)

---

### 🔴 Problema 2: Confusão de Identidade - MARCIO RICARDO vs Parada Obrigatória

**O Que Aconteceu** (29/08 13:53):
```
[db_audit.php executado]
Tenant ID 30:
  ✓ Slug: 'lite' → 'paradaobrigatoriavilas'
  ✓ Nome Empresa: NULL → 'Parada Obrigatória'
  ✓ Logo URL: corrigida para 'uploads/tenants/30/logo.png'
```

**Resultado**:
- A instalação **MARCIO RICARDO** (UUID 5579fd18-b48e-473e-a...) foi renomeada como **Parada Obrigatória**
- Mas há **outra instalação** (UUID 4aa0051b-2af1-44a4-a...) não encontrada
- **Dashboard SaaS** pode estar confundindo as duas

---

### 🟡 Problema 3: Deadlocks Recorrentes no Banco de Dados

**Erro Recente** (01/09 13:36:43):
```
SQLSTATE[40001]: Serialization failure
Deadlock found when trying to get lock
(3 ocorrências em 4 segundos)
```

**Possível Causa**:
- Contention no acesso a tabelas (múltiplas conexões simultâneas)
- Transações longas não liberando locks
- Possível corrupção de índices

**Impacto**:
- ❌ Transações podem falhar durante operação
- ❌ Pode impedir sincronização MasterSync
- ⚠️ Performance degradada

---

### 🟡 Problema 4: Erro de Sintaxe SQL em search_8080.php

**Erro** (01/09 08:25:28):
```
SQLSTATE[42000]: Syntax error near 'text' at line 1
Arquivo: Y:\bt-enterprise-lite\search_8080.php
Linha: 12
```

**Impacto**:
- Script de busca não funciona
- Impossível investigar dados de 8080 (provavelmente porta de sincronização)

---

## ESTADO ATUAL DAS DUAS VMS

### ✅ VM BANCADA (Mesmo local de MARCIO RICARDO)
```
Hardware:
  - Hostname: [Windows VM]
  - IP Local: 192.168.100.245
  - IP Impressora: 192.168.100.126
  
Aplicação:
  - Tipo: BT Queue Enterprise Lite v4.0.0
  - Banco: bt_enterprise_saas (MariaDB)
  - Modo: cloud
  - Licença: ATIVA até 22/08/2027
  
Status:
  - DB: ✅ ONLINE (com issues)
  - Sync: ❌ OFFLINE (sem comunicação master)
  - Operação: ⚠️ DEGRADADA (operando offline)
```

### ❌ PARADA OBRIGATÓRIA VILAS (LOCAL DESCONHECIDO)
```
Informações do Painel SaaS:
  - Status: OFFLINE
  - Última sincronização: Nunca
  - Versão: v1.0.0
  - UUID: 4aa0051b-2af1-44a4-a361-ea063c1aaf02

Informações Locais:
  - NÃO ENCONTRADA em Y:\bt-enterprise-lite
  - NÃO ENCONTRADA no banco bt_enterprise_saas
  - LOCAL PROVÁVEL: Outro servidor/VM não investigado
```

---

## CHECKLIST DE AUDITORIA

### Sincronização
- ❌ MasterSync: Falhando desde 26/07 (HTTP 404)
- ✅ Licença: ATIVA no BD
- ⚠️ Offline Permitido: 7 dias (limite se aplicado)
- ❌ Última validação de licença: NULL (nunca)

### Banco de Dados
- ❌ Deadlocks: Detectados em 01/09
- ❌ Sintaxe SQL: Erro em search_8080.php
- ✅ Estrutura: Tables existem e têm dados
- ⚠️ Integridade: Não foi verificada

### Operações
- ✅ Serviços: 6 configurados
- ✅ Operadores: 4 cadastrados
- ⚠️ Tickets: Apenas 3 (última data: 22/08)
- ✅ Guichês: 3 operacionais

### Segurança
- ✅ Tokens: Configurados
- ✅ Hardware ID: Registrado
- ⚠️ Passwords em DB: Hardcoded em config.php (RISCO)
- ❌ SSL/TLS: URL de master não usa HTTPS

### Dados da Segunda Instalação
- ❌ UUID 4aa0051b-2af1-44a4-a: Não existe localmente
- ❌ Nome "Parada Obrigatória Vilas": Não encontrado em BD
- ⚠️ Versão v1.0.0: Arquivo não encontrado
- ❌ Local de instalação: DESCONHECIDO

---

## RECOMENDAÇÕES (SEM EXECUTAR)

### 🔴 CRÍTICO - Deve ser resolvido em 24h:
1. **Restaurar sincronização MasterSync**
   - Verificar se `http://api.brandaotech.com.br:8080/api/v1/sync.php` está funcionando
   - Testar conectividade com telnet/curl
   - Revisar logs da plataforma master para erro 404

2. **Localizar segunda instalação (UUID 4aa0051b-2af1-44a4-a)**
   - Procurar em outro servidor/VM
   - Verificar se está com licença suspensa
   - Verificar se necessita reativação

3. **Resolver deadlocks no banco de dados**
   - Executar ANALYZE TABLE nas tabelas afetadas
   - Verificar índices para redundância
   - Considerar otimizar queries longas

### 🟡 IMPORTANTE - Próximos 7 dias:
4. **Corrigir erro SQL em search_8080.php**
   - Revisar linha 12 do arquivo
   - Validar sintaxe SQL
   - Testar execução

5. **Auditar aplicação de 7 dias offline**
   - Se sincronização continuar quebrada após 7 dias, sistema pode bloquear

6. **Criptografar passwords em config.php**
   - Mover de arquivo de texto para arquivo protegido
   - Usar variáveis de ambiente

### 💡 MELHORIAS - Próximas 2 semanas:
7. **Unificar instalações no dashboard SaaS**
   - Resolver confusão MARCIO RICARDO vs Parada Obrigatória
   - Atualizar slugs para refletir corretamente

8. **Implementar monitoring de sincronização**
   - Alertas quando MasterSync falha por > 1h
   - Dashboard de status de todas as instalações

9. **Backup de segurança**
   - Backup completo antes de qualquer correção
   - Manter histórico de mudanças

---

## DADOS TÉCNICOS PARA REFERÊNCIA

### Último Backup Disponível
- **Arquivo**: `backup_manual_20260822_161458.sql`
- **Data**: 22/08/2026
- **Tamanho**: Completo (tenants, serviços, senhas, configurações, licenças)
- **Estado**: Contém apenas Tenant 30 (MARCIO RICARDO)

### Versão Aplicação
- **Versão Config**: 4.0.0
- **Timezone**: America/Bahia
- **Charset**: utf8mb4
- **Debug**: false

### Mariadb Info (de config.php)
```
Host: 127.0.0.1
DB: bt_enterprise_saas
User: bt_saas_user
Charset: utf8mb4
```

---

## CONCLUSÃO

**Status Geral**: 🔴 **CRÍTICO - AÇÃO NECESSÁRIA**

A auditoria revelou dois problemas principais:

1. **Sincronização quebrada** desde 26/07 impedindo comunicação com master
2. **Segunda instalação desaparecida** ou em servidor diferente não acessível

Uma instalação (MARCIO RICARDO) está operacional no BD mas OFFLINE na sincronização. A outra (Parada Obrigatória Vilas) não existe nos registros locais.

**Próximo passo**: Verificar conectividade com `api.brandaotech.com.br:8080` e localizar segunda VM.

---

**Relatório gerado em**: 01/09/2026 16:45  
**Próxima auditoria recomendada**: 02/09/2026 (após resolução de críticos)  
**Assinado por**: Auditoria Automática (sem alterações)
