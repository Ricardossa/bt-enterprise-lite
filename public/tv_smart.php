<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

// Resolve o Tenant (Isolamento SaaS)
$tenantId = Auth::tenantId();
if ($tenantId <= 0) {
    die("<h1>❌ Unidade não identificada.</h1>");
}

// Busca Identidade da Unidade
$config = [];
try {
    $rows = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE tenant_id = ?", [$tenantId]);
    foreach ($rows as $r) { $config[$r['chave']] = $r['valor']; }
} catch (Exception $e) {}

$empresa = $config['empresa'] ?? 'BT Queue SMART';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#040d16">
    <title>BT SMART TV - <?= htmlspecialchars($empresa) ?></title>
    <link rel="stylesheet" href="assets/css/tv_smart.css?v=2.7.1&t=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

    <!-- CABEÇALHO BRANDING -->
    <header class="tv-header-smart">
        <div style="display:flex; align-items:center; gap:25px;">
            <?php
                // [v1.8.5] Busca Logo Dinâmica por Unidade (SaaS Safe)
                $logoUrl = $config['logo_url'] ?? 'uploads/logo.png';
                if (empty($logoUrl) || (!str_starts_with($logoUrl, 'http') && !file_exists(__DIR__ . '/' . $logoUrl))) {
                    $logoUrl = 'https://api.brandaotech.com.br/uploads/logo/logo.png';
                }
            ?>
            <img src="<?= $logoUrl ?>" style="max-height: 80px; filter: drop-shadow(0 0 10px rgba(29, 180, 255, 0.3));">
            <h1 style="margin:0; font-size: 42px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase;"><?= htmlspecialchars($empresa) ?></h1>
        </div>
        <div id="tv-clock" class="tv-clock-smart">00:00:00</div>
        <div class="alexa-ring"></div>
    </header>

    <!-- OVERLAY DE CHAMADA (ALEXA MODE) -->
    <div id="call-overlay" class="interruption-overlay">
        <div style="font-size: 30px; color: var(--secondary-smart); font-weight: 900; text-transform: uppercase; letter-spacing: 5px;">Aguardamos por você</div>
        <h1 id="giant-ticket" class="ticket-giant-smart">---</h1>
        <div id="giant-name" class="name-giant-smart">---</div>
        <div id="giant-guiche" style="font-size: 40px; margin-top: 40px; background: #fff; color: #000; padding: 10px 40px; border-radius: 50px; font-weight: 900;">CADEIRA --</div>
    </div>

    <div class="tv-layout-smart">

        <!-- MURAL DINÂMICO (CARROSSEL) - 75% da tela -->
        <div class="smart-carousel-container">

            <!-- SLIDE 1: AGENDA DE HOJE -->
            <div id="slide-agenda" class="smart-slide animate__animated animate__fadeIn">
                <h1 class="slide-title-smart"><i class="fa-solid fa-calendar-day" style="color: var(--accent-smart);"></i> AGENDA DE HOJE</h1>
                <div id="container-agenda" class="agenda-grid"></div>
            </div>

            <!-- SLIDE 2: PROMOÇÕES -->
            <div id="slide-promos" class="smart-slide animate__animated animate__fadeIn">
                <h1 class="slide-title-smart"><i class="fa-solid fa-tag" style="color: var(--accent-smart);"></i> OFERTAS IMPERDÍVEIS</h1>
                <div id="container-promos" style="width: 100%; flex: 1; display: flex; flex-direction: column; justify-content: center;"></div>
            </div>

            <!-- SLIDE 3: FILA ATUAL -->
            <div id="slide-fila" class="smart-slide animate__animated animate__fadeIn">
                <h1 class="slide-title-smart"><i class="fa-solid fa-users" style="color: var(--accent-smart);"></i> QUEM ESTÁ CHEGANDO</h1>
                <div id="container-fila" class="agenda-grid"></div>
            </div>

            <!-- SLIDE 4: CLUBE DE VANTAGENS -->
            <div id="slide-clube" class="smart-slide animate__animated animate__fadeIn">
                <h1 class="slide-title-smart"><i class="fa-solid fa-crown" style="color: var(--accent-smart);"></i> CLUBE DE VANTAGENS</h1>
                <div id="container-clube" style="width: 100%; flex: 1; display: flex; flex-direction: column; justify-content: center;"></div>
            </div>

            <!-- SLIDE 5: PROGRAMA FIDELIDADE -->
            <div id="slide-fidelidade" class="smart-slide animate__animated animate__fadeIn">
                <h1 class="slide-title-smart"><i class="fa-solid fa-gift" style="color: var(--accent-smart);"></i> PROGRAMA FIDELIDADE</h1>
                <div id="container-fidelidade" style="width: 100%; flex: 1; display: flex; flex-direction: column; justify-content: center;"></div>
            </div>

        </div>

        <!-- SIDEBAR DE HISTÓRICO - 25% da tela -->
        <aside class="smart-sidebar-history">
            <h2 class="history-title-smart"><i class="fa-solid fa-history"></i> ÚLTIMOS</h2>
            <div id="container-history" class="history-list-smart">
                <!-- Injetado via JS -->
            </div>
        </aside>

    </div>

    <script src="assets/js/api.js"></script>
    <script src="assets/js/tv_smart.js?v=2.7.0&t=<?= time() ?>"></script>
</body>
</html>
