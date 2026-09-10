<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\AIService;
use BTQueue\Core\QueueService;

header('Content-Type: application/json; charset=utf-8');

// Apenas administradores podem consultar a inteligência
Auth::protegerAPI('ADMIN');

try {
    $ai = new AIService();
    $queue = new QueueService();

    // 1. Coleta estatísticas de HOJE
    $stats = $queue->getStatsPorPeriodo();

    // 2. Solicita análise ao Cérebro (TrueNAS Xeon)
    $analise = $ai->generateDailyReport($stats);

    echo json_encode([
        'success' => true,
        'analise' => $analise
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Falha ao consultar a Inteligência Artificial local."
    ]);
}
