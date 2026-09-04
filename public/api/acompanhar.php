<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

$uuid = trim($_GET['uuid'] ?? '');

if ($uuid === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'UUID não informado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Detecta colunas da tabela senhas (SaaS Safe)
    $cols = Database::getTableColumns('senhas');
    $campoCodigo = in_array('codigo', $cols) ? 's.codigo' : 's.senha';

    // 1. Busca os dados VITAIS da senha primeiro (Query Simples para não falhar)
    // [LITE v4.1.0] Blindagem SaaS: Exige que a senha pertença à Unidade atual
    $tenantId = \BTQueue\Core\Auth::tenantId();
    $senha = Database::fetch("
        SELECT
            s.id,
            $campoCodigo as codigo_real,
            s.status,
            s.guiche_id,
            s.servico_id
        FROM senhas s
        WHERE s.cliente_uuid = ? AND s.tenant_id = ?
        LIMIT 1
    ", [$uuid, $tenantId]);

    if (!$senha) {
        echo json_encode(['success' => false, 'message' => 'Senha não encontrada.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Busca informações do Serviço e Guichê (Opcionais)
    $servico = Database::fetch("SELECT nome, icone, cor, tempo_medio FROM servicos WHERE id = ?", [(int)$senha['servico_id']]);
    $guiche = Database::fetch("SELECT nome FROM guiches WHERE id = ?", [(int)$senha['guiche_id']]);

    // 3. Calcula Posição (Incluindo Congeladas para estabilidade)
    $posicaoRes = Database::fetch("
        SELECT COUNT(*) AS total
        FROM senhas
        WHERE status IN ('AGUARDANDO', 'CONGELADA')
          AND servico_id = ?
          AND id < ?
    ", [(int)$senha['servico_id'], (int)$senha['id']]);

    $pessoas = (int)($posicaoRes['total'] ?? 0);
    $isFrozen = ($senha['status'] === 'CONGELADA');

    // 4. Monta a resposta blindada
    echo json_encode([
        'success' => true,
        'data' => [
            'senha'   => $senha['codigo_real'],
            'status'  => $senha['status'],
            'servico_id' => (int)$senha['servico_id'], // v2.3.1: Vital para ocultação de botões
            'posicao' => $isFrozen ? '--' : $pessoas,
            'guiche'  => $guiche ? $guiche['nome'] : '--',
            'servico' => $servico ? $servico['nome'] : 'Atendimento',
            'icone'   => $servico ? $servico['icone'] : '📋',
            'cor'     => $servico ? $servico['cor'] : '#1565C0',
            'tempo_estimado' => $isFrozen ? 0 : ($pessoas * (int)($servico['tempo_medio'] ?? 10)),
            'mensagem' => match ($senha['status']) {
                'AGUARDANDO'  => ($pessoas === 0) ? '🟢 Você é o próximo da fila.' : "🟡 Há $pessoas pessoa(s) à sua frente.",
                'CONGELADA'   => '❄️ Sua senha está reservada. Aguardando término do atendimento atual.',
                'CHAMANDO'    => '🔔 SUA SENHA ESTÁ SENDO CHAMADA!',
                'ATENDIMENTO' => '👨‍⚕️ Você está em atendimento.',
                'FINALIZADA'  => '✅ Atendimento finalizado.',
                default       => 'Aguardando atualização...'
            }
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno ao processar dados da senha.'
    ]);
}
