<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\DashboardController;
use BTQueue\Core\ActivityService;
use BTQueue\Core\Auth;

Auth::protegerAPI();

try {
    $dashboard = new DashboardController();

    // Força o sistema a estar sempre "vivo"
    $dashboard->ensureActivityIsAlive();

    $stats = $dashboard->getStats();
    $health = $dashboard->getHealth();

    // Busca atividades (SaaS Safe via Service)
    $activities = ActivityService::getRecent(10);

    // Obtém status da licença local
    $licenca = \BTQueue\Core\Database::fetch("SELECT status, ultima_validacao FROM licencas LIMIT 1");

    // [NOVO] Cálculo de Ganhos Líquidos (Comissão do Barbeiro)
    $operadorLogado = Auth::operador();
    $ganhosLiquidos = $stats['ganhos_hoje'] ?? 0;

    if ($operadorLogado && $operadorLogado['nivel'] !== 'ADMIN') {
        $opDb = Database::fetch("SELECT comissao FROM operadores WHERE id = ?", [$operadorLogado['id']]);
        $perc = (float)($opDb['comissao'] ?? 50.00);
        $ganhosLiquidos = ($ganhosLiquidos * $perc) / 100;
    }

    echo json_encode([
        'success' => true,
        'stats' => array_merge($stats, ['ganhos_hoje' => $ganhosLiquidos]), // Sobrescreve o valor total pelo líquido
        'health' => $health,
        'activities' => $activities,
        'license' => [
            'status' => $licenca['status'] ?? 'N/A',
            'last_sync' => $licenca['ultima_validacao'] ?? 'Nunca'
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
