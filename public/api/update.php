<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

Auth::protegerAPI('ADMIN');

// [LITE v2.6.1] MÓDULO DE UPDATE OTA DESATIVADO
// Na versão Lite, as atualizações são aplicadas manualmente pelo administrador.

echo json_encode([
    'success' => true,
    'message' => 'Versão Lite: Atualizações automáticas desativadas.',
    'data' => [
        'current_version' => defined('BT_VERSION') ? BT_VERSION : '2.6.1',
        'update_available' => false
    ]
], JSON_UNESCAPED_UNICODE);
