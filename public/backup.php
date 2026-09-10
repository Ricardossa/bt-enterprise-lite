<?php
declare(strict_types=1);

/**
 * Script de Acionamento de Backup - Enterprise
 * Pode ser chamado via Cron ou via Browser por um Admin.
 */

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Backup\BackupService;
use BTQueue\Core\Auth;

// Se for via Web, exige Admin
if (php_sapi_name() !== 'cli') {
    Auth::protegerPagina('ADMIN');
}

$service = new BackupService();
$res = $service->run();

if (php_sapi_name() === 'cli') {
    echo $res['success'] ? "✅ Backup concluído: " . $res['message'] : "❌ Falha: " . $res['message'];
    echo "\n";
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
