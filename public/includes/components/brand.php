<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BRAND TECH INTEGRATION
| BT Queue Enterprise - Brand Engine v4.7.4 (Restored)
|--------------------------------------------------------------------------
*/

// MOTOR DE BOOTSTRAP (Unificado)
if (!defined('BT_BOOTSTRAP')) {
    $bootFile = null;
    $searchPaths = [
        dirname(__DIR__, 3) . '/bootstrap.php', // Ambiente Instalador
        __DIR__ . '/../../bootstrap.php'        // Ambiente Desenvolvimento
    ];
    foreach ($searchPaths as $path) {
        if (file_exists($path)) { $bootFile = $path; break; }
    }
    if ($bootFile) require_once $bootFile;
    define('BT_BOOTSTRAP', true);
}

use BTQueue\Core\Database;

// 1. CÁLCULO DE BASE_URL (Agnóstico a pastas)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script = $_SERVER['SCRIPT_NAME'];
$publicDir = str_replace(basename($script), '', $script);
$baseUrl = $protocol . $host . $publicDir;

// Saneamento de URL para subpastas
if (strpos($baseUrl, '/api/') !== false) $baseUrl = str_replace('/api/', '/', $baseUrl);
if (strpos($baseUrl, '/live_premium/') !== false) $baseUrl = str_replace('/live_premium/', '/', $baseUrl);
if (strpos($baseUrl, '/includes/components/') !== false) $baseUrl = str_replace('/includes/components/', '/', $baseUrl);

$config = [];
$empresa = 'BT Queue Enterprise';

try {
    $linhas = Database::fetchAll("SELECT chave, valor FROM configuracoes");
    foreach ($linhas as $linha) { $config[$linha['chave']] = $linha['valor']; }
    $empresa = $config['empresa'] ?? 'BT Queue Enterprise';
} catch (Throwable $e) {}

// 2. LOGO ADMIN (URL Atômica)
$logoPath = dirname(__DIR__, 2) . '/uploads/logo.png';
$logoExiste = is_file($logoPath);
$logoUrl = $logoExiste ? $baseUrl . 'uploads/logo.png?v=' . filemtime($logoPath) : '';
