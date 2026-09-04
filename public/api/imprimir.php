<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {
    $dados = json_decode(file_get_contents('php://input'), true);

    if (!$dados || !isset($dados['senha'])) {
        throw new Exception("Dados de impressão incompletos.");
    }

    $senha = (string) $dados['senha'];
    $servico = (string) ($dados['servico'] ?? 'Atendimento');
    $empresa = (string) ($dados['empresa'] ?? 'BT Queue');
    $data = date('d/m/Y H:i:s');

    // 1. Obter nome da impressora das configurações (Escopado por Unidade)
    $tenantId = \BTQueue\Core\Auth::tenantId();
    $cfg = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'printer_name' AND tenant_id = ? LIMIT 1", [$tenantId]);
    $printerName = $cfg ? $cfg['valor'] : 'BT_TICKET';

    // 2. Montar o conteúdo do Ticket (Texto Puro para Térmica)
    // Usamos caracteres simples para garantir compatibilidade máxima
    $conteudo = "\x1B\x40"; // ESC @ - Inicializa impressora
    $conteudo .= "\x1B\x61\x01"; // ESC a 1 - Alinhamento Centralizado

    $conteudo .= "--------------------------------\n";
    $conteudo .= strtoupper($empresa) . "\n";
    $conteudo .= "SISTEMA DE SENHAS\n";
    $conteudo .= "--------------------------------\n\n";

    $conteudo .= "SUA SENHA E:\n";
    $conteudo .= "\x1B\x21\x30"; // Texto Gigante
    $conteudo .= $senha . "\n";
    $conteudo .= "\x1B\x21\x00"; // Volta ao normal

    $conteudo .= strtoupper($servico) . "\n\n";

    $conteudo .= "Data: " . $data . "\n";
    $conteudo .= "Aguarde ser chamado no painel.\n";
    $conteudo .= "--------------------------------\n\n\n\n\n";
    $conteudo .= "\x1D\x56\x41"; // GS V 65 - Corte Parcial (Se suportado)

    // 3. Salvar em arquivo temporário
    $tempFile = dirname(__DIR__, 2) . '/cache/tickets/ticket_' . time() . '_' . rand(100, 999) . '.txt';
    file_put_contents($tempFile, $conteudo);

    // 4. Enviar para o Spooler do Windows
    // O Windows permite enviar arquivos diretamente para impressoras compartilhadas via IP local
    $command = "copy /b \"" . str_replace('/', '\\', $tempFile) . "\" \"\\\\127.0.0.1\\" . $printerName . "\"";

    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);

    // 5. Limpeza
    @unlink($tempFile);

    if ($returnVar !== 0) {
        throw new Exception("Falha ao enviar para o spooler da impressora. Verifique se ela esta compartilhada como '" . $printerName . "'.");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Impressão disparada no servidor.',
        'printer' => $printerName
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
