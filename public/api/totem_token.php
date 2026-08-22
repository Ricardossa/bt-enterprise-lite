<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {
    $config = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'qr_security_salt'");
    $salt = $config['valor'] ?? 'default_salt';

    // O token é um hash do salt + minuto atual
    // Mudamos a cada minuto para evitar que o link salvo funcione por muito tempo
    $timeKey = date('YmdHi');
    $token = md5($salt . $timeKey);

    echo json_encode([
        'success' => true,
        'token' => $token,
        'expires_in' => 60 - (int)date('s') // segundos para o próximo minuto
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
