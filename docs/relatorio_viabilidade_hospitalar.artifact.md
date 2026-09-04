# 🏥 Relatório de Viabilidade: Integração Hospitalar (V4.5)
**Brandão Tech Integration - Arquiteto de Software**

## 1. Objetivo Técnico
Permitir que a **BT Queue Enterprise** opere em ambientes hospitalares como um **Motor de Chamada Aumentado**, integrando-se passivamente a ERPs/HIS (como MV, Tasy, Wareline) via APIs e Webhooks.

---

## 2. Pilares da Arquitetura "Agnóstica"

O sistema deixará de ser dependente de uma "emissão interna" e passará a aceitar **Eventos Externos**.

### 🌉 O Módulo Integration Hub
Criaremos uma camada intermediária que recebe requisições de sistemas de terceiros e as normaliza para o motor da Enterprise.

- **Endpoint REST**: `POST /api/v1/integration/call`
- **Payload Sugerido**:
```json
{
  "protocolo_origem": "HIS-2026-990",
  "nome_pacote": "MARIA SILVA",
  "local": "CONSULTÓRIO 05",
  "profissional": "DR. RICARDO BRANDÃO",
  "prioridade": "PREFERENCIAL"
}
```

---

## 3. Matriz de Impacto

| Componente | Mudança Necessária | Risco |
| :--- | :--- | :--- |
| **Banco de Dados** | Adicionar colunas `nome_cliente` e `atendente_nome` na tabela `senhas`. | Zero (Non-destructive) |
| **TV de Atendimento** | Novo template dinâmico que foca no Nome em vez do Código. | Baixo |
| **QueueService** | Método para registrar senhas "externas" sem passar pelo Totem. | Baixo |
| **NOC Panel** | Filtro para ocultar/mostrar funções de Totem em modo Hospital. | Zero |

---

## 4. Viabilidade Comercial (Market Share)

Ao implementar esta integração, a BT Queue Enterprise triplica seu mercado potencial:
1. **Laboratórios**: Chamada pelo nome para coleta.
2. **Clínicas de Imagem**: Chamada para exames específicos.
3. **Hospitais Dia**: Gestão de fluxo de pré-operatório.

**Diferencial**: O cliente não precisa "treinar os médicos" para usar um novo sistema. Eles continuam no ERP deles, e a nossa TV atualiza sozinha.

---

## 5. Roadmap de Implementação

- **[ ] SPR-01**: Refatoração do Banco de Dados (Novos campos de metadados).
- **[ ] SPR-02**: Desenvolvimento do `IntegrationHubController.php`.
- **[ ] SPR-03**: Protótipo da TV Hospitalar (Exibição de Nomes).
- **[ ] SPR-04**: Mock de Integração (Script simulando saída do sistema MV).

---
© 2026 **Brandão Tech Integration**.
*Estratégia Diamond de Expansão de Software.*
