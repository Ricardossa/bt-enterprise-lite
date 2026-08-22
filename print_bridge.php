<?php
/**
 * BT QUEUE - PRINT BRIDGE v1.2 (DIAMOND EDITION)
 * Este script transforma seu Windows em um Servidor de Impressão para a Nuvem.
 */

// --- CONFIGURAÇÃO DE SEGURANÇA E CORS ---
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responde ao pre-flight do navegador
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Pasta de trabalho absoluta para evitar erros de permissão
$workDir = __DIR__;
$logFile = $workDir . '/logs/print_service.log';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true);

        if (!$dados || !isset($dados['senha'])) {
            throw new Exception("Dados invalidos ou vazios recebidos.");
        }

        $senha   = $dados['senha'];
        $servico = $dados['servico'] ?? 'Atendimento';
        $empresa = $dados['empresa'] ?? 'BT Queue';
        $printer = $dados['printer'] ?? 'BT_TICKET';
        $data    = date('d/m/Y H:i:s');

        // Log visual no console do Windows
        file_put_contents('php://stdout', "\n[" . date('H:i:s') . "] 🖨️  IMPRIMINDO: Senha $senha ($servico)\n");

        // Formatação ESC/POS (Padrão para impressoras térmicas)
        $ticket = "\x1B\x40"; // Inicializa
        $ticket .= "\x1B\x61\x01"; // Centraliza
        $ticket .= "--------------------------------\n";
        $ticket .= strtoupper($empresa) . "\n";
        $ticket .= "--------------------------------\n\n";
        $ticket .= "SUA SENHA:\n";
        $ticket .= "\x1B\x21\x30" . $senha . "\n\x1B\x21\x00"; // Grande
        $ticket .= strtoupper($servico) . "\n\n";
        $ticket .= "Data: " . $data . "\n";
        $ticket .= "--------------------------------\n\n\n\n\n";
        $ticket .= "\x1D\x56\x41"; // Corte total/parcial

        $tempFile = $workDir . DIRECTORY_SEPARATOR . 'temp_ticket.bin';
        file_put_contents($tempFile, $ticket);

        // Comando de envio direto para a porta do Windows
        $cmd = "copy /b \"" . $tempFile . "\" \"\\\\127.0.0.1\\$printer\"";

        $output = [];
        $res = -1;
        exec($cmd . " 2>&1", $output, $res);
        @unlink($tempFile);

        if ($res === 0) {
            file_put_contents('php://stdout', "   ✅ Sucesso: Enviado para a impressora $printer\n");
            echo json_encode(['success' => true, 'message' => 'Impresso com sucesso']);
        } else {
            $msg = implode(" ", $output);
            file_put_contents('php://stdout', "   ❌ Erro: $msg\n");
            throw new Exception($msg);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Se for GET (Acesso via navegador para teste)
echo "======================================================\n";
echo "          BT QUEUE - PRINT BRIDGE v1.2\n";
echo "======================================================\n\n";
echo "ESTADO: Motor de Impressão ONLINE 🚀\n";
echo "IP LOCAL: " . gethostbyname(gethostname()) . "\n";
echo "PORTA: 8001\n";
echo "IMPRESSORA: BT_TICKET\n\n";
echo "Aguardando pedidos de impressão...";
