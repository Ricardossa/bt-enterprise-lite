<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\ScheduleService;
use BTQueue\Core\Database;

$msg = '';
$sucesso = false;
$showConfirm = false;
$token = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = strtoupper(trim((string)($_POST['token'] ?? '')));
    if ($token) {
        $service = new ScheduleService();
        $res = $service->cancelar($token);
        $msg = $res['message'];
        $sucesso = $res['success'];
    } else {
        $msg = "Informe o código de cancelamento.";
    }
} elseif (isset($_GET['t'])) {
    $token = strtoupper(trim((string)$_GET['t']));
    $showConfirm = true; // Ativa a barreira contra cancelamento acidental
}

$config = [];
try {
    $linhas = Database::fetchAll("SELECT chave, valor FROM configuracoes");
    foreach ($linhas as $linha) { $config[$linha['chave']] = $linha['valor']; }
} catch (Exception $e) {}

$empresa = $config['empresa'] ?? 'Brandão Tech';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelar Agendamento - <?= htmlspecialchars($empresa) ?></title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        body { background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .cancel-card { background: var(--card); width: 100%; max-width: 450px; border-radius: 20px; border: 1px solid var(--border); padding: 40px; text-align: center; }
    </style>
</head>
<body>

<div class="cancel-card">
    <h2 style="font-weight: 900; color: #fff;">CANCELAMENTO</h2>
    <p style="color: var(--text2); margin-bottom: 30px;">Informe seu código para desmarcar o horário.</p>

    <?php if ($msg): ?>
        <div style="padding: 20px; border-radius: 10px; margin-bottom: 20px; background: <?= $sucesso ? 'rgba(24,201,100,0.1)' : 'rgba(255,77,77,0.1)' ?>; color: <?= $sucesso ? '#18C964' : '#ff4d4d' ?>; border: 1px solid <?= $sucesso ? '#18C964' : '#ff4d4d' ?>;">
            <?= $msg ?>
        </div>
    <?php endif; ?>

    <?php if ($showConfirm): ?>
    <div style="background: rgba(245,165,36,0.1); border: 1px solid var(--warning); padding: 25px; border-radius: 15px; margin-bottom: 20px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 40px; color: var(--warning); margin-bottom: 15px;"></i>
        <h3 style="color: #fff; margin-bottom: 10px;">Deseja desmarcar seu horário?</h3>
        <p style="color: var(--text2); font-size: 14px; margin-bottom: 20px;">Você está prestes a cancelar o agendamento código: <b><?= $token ?></b>.</p>
        <form method="POST">
            <input type="hidden" name="token" value="<?= $token ?>">
            <button type="submit" class="bt-button bt-danger" style="width: 100%; padding: 15px; font-weight: bold;">SIM, CANCELAR AGORA</button>
        </form>
    </div>
    <?php elseif (!$sucesso): ?>
    <form method="POST">
        <input type="text" name="token" class="form-control" placeholder="CÓDIGO (Ex: 8A2B3C)" style="text-align: center; font-size: 24px; font-weight: bold; margin-bottom: 20px; background: var(--sidebar); border: 1px solid var(--border); color: #fff; height: 60px; border-radius: 10px; width: 100%;">
        <button type="submit" class="bt-button bt-primary" style="width: 100%; padding: 15px; font-weight: bold;">BUSCAR AGENDAMENTO</button>
    </form>
    <?php endif; ?>

    <a href="agendar.php" class="bt-button" style="display: block; margin-top: 20px; text-decoration: none; background: var(--sidebar); border: 1px solid var(--border);">VOLTAR AO AGENDAMENTO</a>
</div>

</body>
</html>
