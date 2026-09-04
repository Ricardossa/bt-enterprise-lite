<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BRAND TECH INTEGRATION
| BT Queue Enterprise Lite
| Identidade por Tenant
|--------------------------------------------------------------------------
*/

if (!defined('BT_BOOTSTRAP')) {
    $bootFile = null;

    $searchPaths = [
        dirname(__DIR__, 3) . '/bootstrap.php',
        __DIR__ . '/../../bootstrap.php'
    ];

    foreach ($searchPaths as $path) {
        if (file_exists($path)) {
            $bootFile = $path;
            break;
        }
    }

    if ($bootFile) {
        require_once $bootFile;
    }

    define('BT_BOOTSTRAP', true);
}

use BTQueue\Core\Auth;
use BTQueue\Core\Database;

/*
|--------------------------------------------------------------------------
| TENANT ATUAL
|--------------------------------------------------------------------------
*/

$tenantId = Auth::tenantId();

/*
|--------------------------------------------------------------------------
| IDENTIDADE DA UNIDADE
|--------------------------------------------------------------------------
*/

$config = [];

$empresa = 'BT Queue Enterprise';

try {

    if ($tenantId > 0) {

        $linhas = Database::fetchAll(
            "SELECT chave, valor
             FROM configuracoes
             WHERE tenant_id = ?",
            [$tenantId]
        );

        foreach ($linhas as $linha) {
            $config[$linha['chave']] = $linha['valor'];
        }

        $empresa = $config['empresa'] ?? $empresa;
    }

} catch (Throwable $e) {
    // Mantém identidade padrão caso a configuração não esteja disponível.
}

/*
|--------------------------------------------------------------------------
| LOGO DA UNIDADE
|--------------------------------------------------------------------------
*/

$logoExiste = false;
$logoUrl = '';

if ($tenantId > 0) {

    $logoPath = dirname(__DIR__, 2)
        . '/uploads/tenants/'
        . $tenantId
        . '/logo.png';

    if (is_file($logoPath)) {

        $logoExiste = true;

        $protocol = (
            !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off'
        )
            ? 'https://'
            : 'http://';

        $host = $_SERVER['HTTP_HOST'] ?? '';

        $script = $_SERVER['SCRIPT_NAME'] ?? '';

        $publicDir = str_replace(
            basename($script),
            '',
            $script
        );

        $baseUrl = $protocol . $host . $publicDir;

        /*
         * Saneamento para páginas dentro de subpastas.
         */
        if (strpos($baseUrl, '/api/') !== false) {
            $baseUrl = str_replace('/api/', '/', $baseUrl);
        }

        if (strpos($baseUrl, '/live_premium/') !== false) {
            $baseUrl = str_replace('/live_premium/', '/', $baseUrl);
        }

        if (strpos($baseUrl, '/includes/components/') !== false) {
            $baseUrl = str_replace(
                '/includes/components/',
                '/',
                $baseUrl
            );
        }

        $logoUrl =
            $baseUrl
            . 'uploads/tenants/'
            . $tenantId
            . '/logo.png?v='
            . filemtime($logoPath);
    }
}

/*
|--------------------------------------------------------------------------
| VERSÃO
|--------------------------------------------------------------------------
*/

$versao = defined('BT_VERSION')
    ? BT_VERSION
    : '4.0.0';
