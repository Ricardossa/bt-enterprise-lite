<?php
declare(strict_types=1);

/**
 * BT Queue Enterprise - SaaS Gateway
 */

$bootFile = null;
$searchPaths = [
    __DIR__ . '/../bootstrap.php',
    __DIR__ . '/../../bootstrap.php'
];

foreach ($searchPaths as $path) {
    if (file_exists($path)) {
        $bootFile = $path;
        break;
    }
}

if (!$bootFile) die("<h1>❌ ERRO CRÍTICO: Motor não encontrado.</h1>");
require_once $bootFile;

use BTQueue\Core\Database;
use BTQueue\Core\Auth;

// [SaaS v1.0.0] Garante conexão com o banco
try {
    Database::getInstance();
} catch (\Throwable $e) {
    die("<h1>❌ ERRO DE CONEXÃO:</h1><p>" . $e->getMessage() . "</p>");
}

// Direcionamento inteligente
Auth::iniciar();
if (Auth::autenticado()) {
    $op = Auth::operador();
    if ($op['nivel'] === 'ADMIN') {
        header('Location: dashboard.php');
    } else {
        header('Location: barber_operador.php');
    }
} else {
    // Se não estiver logado, manda para o login (ou dashboard que trata o login)
    header('Location: login.php');
}
exit;
