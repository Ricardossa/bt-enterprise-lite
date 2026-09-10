<?php
declare(strict_types=1);

/**
 * BT Integration Hub - Agendamento Externo (Google Calendar)
 * Alimenta a gaveta de agendamentos para o operador chamar no horário.
 */

require_once __DIR__ . '/../../../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

// VALIDAÇÃO DE TOKEN
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$tokenEnviado = $headers['x-integration-token'] ?? '';
$config = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'integration_token'");
$tokenOficial = $config['valor'] ?? 'CEF3504AC887599D03C07639D5C90D63';

if ($tokenEnviado !== $tokenOficial) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

try {
    $dados = json_decode(file_get_contents('php://input'), true);

    $nome = strtoupper(trim((string)($dados['nome_paciente'] ?? '')));
    $dataHora = trim((string)($dados['data_hora'] ?? '')); // Ex: 2026-08-11 09:00:00
    $servicoId = (int)($dados['servico_id'] ?? 1);

    if (empty($nome) || empty($dataHora)) {
        throw new Exception("Nome e Data/Hora são obrigatórios.");
    }

    // --- PROTEÇÃO ANTI-DUPLICIDADE TOTAL (v5.7.1 Diamond) ---
    // Verifica se este paciente já existiu neste horário, independente de já ter sido chamado ou não
    $jaExiste = Database::fetch(
        "SELECT id FROM senhas
         WHERE nome_cliente = ?
         AND data_agendamento = ? LIMIT 1",
        [$nome, $dataHora]
    );

    if ($jaExiste) {
        echo json_encode([
            'success' => true,
            'message' => 'Agendamento já sincronizado anteriormente.',
            'paciente' => $nome
        ]);
        exit;
    }

    $uuid = bin2hex(random_bytes(16));

    // REGISTRA COMO 'AGENDADO'
    Database::execute(
        "INSERT INTO senhas (
            uuid, cliente_uuid, codigo, numero, prefixo,
            nome_cliente, status, data_agendamento, servico_id, created_at, emitida_em
        ) VALUES (
            ?, 'GOOGLE-CALENDAR', 'AGD', 0, 'G',
            ?, 'AGENDADO', ?, ?, ?, ?
        )",
        [$uuid, $nome, $dataHora, $servicoId, $dataHora, $dataHora]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Agendamento registrado com sucesso!',
        'paciente' => $nome,
        'horario' => $dataHora
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
