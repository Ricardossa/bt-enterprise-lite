<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

/**
 * Script de acionamento do Ciclo de Manutenção Local (v2.6.1)
 */

use BTQueue\Core\SyncService;

$sync = new SyncService();
$result = $sync->synchronize();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
