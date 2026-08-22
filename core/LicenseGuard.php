<?php

declare(strict_types=1);

namespace BTQueue\Core;

/**
 * LicenseGuard - O Cão de Guarda da Licença Diamond.
 * Impede a execução do sistema se a licença não for válida.
 */
final class LicenseGuard
{
    public static function protect(): void
    {
        // Ignora proteção se for o script de pulso (para permitir que ele tente reativar)
        if (str_ends_with($_SERVER['SCRIPT_NAME'], 'pulse.php') || str_ends_with($_SERVER['SCRIPT_NAME'], 'setup.php')) {
            return;
        }

        $manager = new LicenseManager();

        // [NOVO] Se estiver bloqueado localmente, tenta um pulso rápido para ver se já liberou na Master
        if ($manager->isBlocked()) {
            $sync = new SyncService();
            $sync->synchronize(); // Tenta sincronizar em tempo real

            // Verifica novamente após a tentativa de sincronia
            if ($manager->isBlocked()) {
                self::renderBlockScreen();
                exit;
            }
        }
    }

    private static function renderBlockScreen(): void
    {
        // Se for uma requisição JSON, envia erro 403
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'LICENÇA SUSPENSA OU EXPIRADA. Entre em contato com o suporte Brandão Tech.',
                'code' => 'LICENSE_BLOCKED'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Caso contrário, renderiza a tela de bloqueio bonitona
        http_response_code(403);
        echo '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>ACESSO SUSPENSO - Brandão Tech</title>
            <style>
                body { background: #081421; color: #fff; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; text-align: center; }
                .card { background: #132238; padding: 50px; border-radius: 20px; border: 2px solid #ff4d4d; box-shadow: 0 0 30px rgba(255, 77, 77, 0.2); max-width: 400px; }
                h1 { color: #ff4d4d; margin-top: 0; }
                p { color: #94a3b8; line-height: 1.6; }
                .btn { display: inline-block; margin-top: 25px; background: #ff4d4d; color: #fff; padding: 12px 30px; border-radius: 10px; text-decoration: none; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>🛑 ACESSO SUSPENSO</h1>
                <p>Identificamos uma pendência na licença desta unidade (<b>'.gethostname().'</b>).<br><br>Por favor, entre em contato com a <b>Brandão Tech</b> para regularizar sua assinatura.</p>
                <a href="#" class="btn" onclick="location.reload()">VERIFICAR NOVAMENTE</a>
                <div style="margin-top: 20px; font-size: 10px; color: #555;">ID: '.hash('crc32', gethostname()).'</div>
            </div>
        </body>
        </html>
        ';
    }
}
