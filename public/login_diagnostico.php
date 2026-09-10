<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;

/**
 * FERRAMENTA DE DIAGNÓSTICO DE LOGIN DIAMOND 💎
 * Use para testar se os seus dados estão corretos no banco.
 */

$msg = "";
$status = "info";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['login'] ?? '';
    $pass = $_POST['senha'] ?? '';

    try {
        if (Auth::login($user, $pass)) {
            $msg = "✅ LOGIN VÁLIDO! O usuário e senha estão corretos e a licença está ativa.";
            $status = "success";
        }
    } catch (\Throwable $e) {
        $msg = "❌ FALHA NO LOGIN: " . $e->getMessage();
        $status = "danger";
    }
}

// Lista usuários para ajudar o teste
$usuarios = Database::fetchAll("SELECT login, nome FROM operadores WHERE ativo = 1");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria de Login - BT Lite</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        body { background: #081421; color: #fff; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .diag-card { background: #132238; padding: 40px; border-radius: 20px; border: 1px solid #1E3552; width: 100%; max-width: 400px; text-align: center; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: bold; }
        .alert-info { background: rgba(29, 180, 255, 0.1); color: #1DB4FF; }
        .alert-success { background: rgba(24, 201, 100, 0.1); color: #18C964; }
        .alert-danger { background: rgba(255, 77, 77, 0.1); color: #FF4D4D; }
        input { width: 100%; padding: 12px; margin-bottom: 10px; border-radius: 8px; border: 1px solid #1E3552; background: #0D1B2A; color: #fff; box-sizing: border-box; }
        button { width: 100%; padding: 15px; border-radius: 12px; border: none; background: #1565C0; color: #fff; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <div class="diag-card">
        <h2>🔍 Auditor de Login</h2>
        <p style="color: #94a3b8; font-size: 12px; margin-bottom: 25px;">Teste se os dados do profissional funcionam no servidor.</p>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $status ?>"><?= $msg ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="login" placeholder="Usuário (Login)" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit">TESTAR CREDENCIAIS</button>
        </form>

        <div style="margin-top: 30px; text-align: left; font-size: 11px; color: #64748b; border-top: 1px solid #1E3552; padding-top: 15px;">
            <b>Usuários Ativos no Sistema:</b><br>
            <?php foreach($usuarios as $u): ?>
                • <?= $u['login'] ?> (<?= $u['nome'] ?>)<br>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
