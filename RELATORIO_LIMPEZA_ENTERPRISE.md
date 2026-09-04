# Relatório de Limpeza Controlada: BT Queue Enterprise (Unidade Y)

Este relatório detalha a organização do projeto **BT Queue Enterprise**, movendo arquivos redundantes e backups para a quarentena, visando um ambiente de desenvolvimento limpo e profissional.

## 📁 Estrutura de Quarentena Criada
Localização: `Y:/bt-enterprise/99_QUARENTENA/`

### 🛠️ Pasta: `public_leftovers/`
Arquivos removidos da pasta `public/` que não são mais necessários para a operação diária.

| Arquivo | Motivo | Risco |
| :--- | :--- | :--- |
| `setup.php` | Script de instalação inicial já executado. | Baixo |
| `tv.php` | Versão legada da TV (Substituída por `tv_v2.php`). | Baixo |
| `audit_full.php`, `check_master_url.php` | Ferramentas de diagnóstico e auditoria. | Baixo |
| `test_enterprise.php` | Script temporário de teste de rota. | Baixo |
| `operador.php.bak_patch023` | Backup manual de correção antiga. | Baixo |

### ⚙️ Pasta: `core_backups/`
Arquivos removidos da pasta `core/` para evitar confusão na lógica do sistema.

| Arquivo | Motivo | Risco |
| :--- | :--- | :--- |
| `QueueService.bkp.php` | Cópia de segurança do motor de filas. | Baixo |
| `QueueService.php.antigo` | Cópia de segurança do motor de filas. | Baixo |
| `ServicoService.php.bak2` | Backup manual de serviço. | Baixo |
| `ServicoService.php.bak3` | Backup manual de serviço. | Baixo |

---

## ✅ Itens Preservados (Core Operacional)
A estrutura raiz da Enterprise agora contém apenas o necessário para o funcionamento:
- `core/`: Lógica ativa e MasterSync.
- `public/`: Telas de operação e APIs ativas.
- `database/`: Schema e Seeds SQL (Migrado para MariaDB SaaS).
- `config/`: Arquivos de configuração da unidade.
- `cache/` & `logs/`: Pastas de sistema.
- `bootstrap.php`: Inicializador.
- `*.bat` & `*.vbs`: Lançadores e serviços de background.
- `print_bridge.php`: Motor de impressão local.

## 🏁 Conclusão
O projeto **BT Queue Enterprise** está agora organizado e pronto para ser inicializado no Git. Todo o histórico de arquivos foi preservado com segurança na pasta `99_QUARENTENA`.

> [!NOTE]
> Nenhuma funcionalidade foi alterada e nenhum código-fonte ativo foi modificado.
