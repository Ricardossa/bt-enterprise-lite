# Plano de Implementação: Check-in Automático via Mobile

Este plano visa corrigir a falha de fluxo onde um cliente com agendamento, ao escanear o totem, não recebe a opção de confirmar sua chegada, sendo forçado a iniciar um novo atendimento.

## User Review Required

> [!IMPORTANT]
> A alteração na API de agendamento tornará o check-in mais flexível, permitindo que o aplicativo identifique o cliente pelo UUID interno em vez de apenas pelo nome digitado. Isso aumenta a precisão e evita erros de digitação no totem físico.

## Proposed Changes

### [Backend API]

#### [MODIFY] [cliente.php](file:///Y:/bt-enterprise-lite/public/api/v1/cliente.php)
- Adicionar lógica para buscar o agendamento do dia atual para o cliente identificado.
- Retornar os detalhes do agendamento (hora, barbeiro, status) no JSON de perfil.

#### [MODIFY] [agenda.php](file:///Y:/bt-enterprise-lite/public/api/v1/agenda.php)
- Atualizar a ação `checkin` para aceitar `cliente_uuid` como critério de busca, além do `query` (nome/token) já existente.
- Garantir que o check-in via mobile funcione com a mesma segurança do totem físico.

### [Frontend Mobile]

#### [MODIFY] [index.php](file:///Y:/bt-enterprise-lite/public/fidelidade/index.php)
- Adicionar um bloco de UI para o "Check-in Rápido" (inicialmente oculto).
- Estilizar o botão para ter destaque visual (ex: cor de aviso/warning).

#### [MODIFY] [loyalty.js](file:///Y:/bt-enterprise-lite/public/fidelidade/assets/js/loyalty.js)
- Atualizar `renderProfile` para detectar a presença de um agendamento no retorno da API.
- Implementar a função `Loyalty.doCheckin()` que realiza a chamada para a API de agendamento e trata o sucesso (exibindo a senha na tela).

## Verification Plan

### Automated Tests
- Não se aplica a este ambiente, mas realizarei testes de requisição manual via logcat/console se necessário.

### Manual Verification
1. Criar um agendamento para um cliente de teste para a data de hoje.
2. Acessar a página de fidelidade simulando o scanner do totem.
3. Verificar se o botão "JÁ CHEGUEI" aparece.
4. Clicar no botão e verificar se o status da senha no banco de dados muda para `PRESENTE`.
5. Confirmar se a senha aparece no celular do cliente após o check-in.
