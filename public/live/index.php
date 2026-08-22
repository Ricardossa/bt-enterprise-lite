<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\Database;

// Carrega Branding do Cliente
$config = [];
try {
    $linhas = Database::fetchAll("SELECT chave, valor FROM configuracoes");
    foreach ($linhas as $linha) { $config[$linha['chave']] = $linha['valor']; }
} catch (Exception $e) {}

$empresa = $config['empresa'] ?? 'BT Queue Enterprise';
$promo_logo = $config['promo_logo'] ?? '../uploads/logo.png';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
    <title>Acompanhamento - <?= htmlspecialchars($empresa) ?></title>

    <link rel="stylesheet" href="assets/css/premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>

<div id="app" class="live-container">

    <!-- HEADER PREMIUM (Fiel Ã  Foto) -->
    <header class="premium-header">
        <i class="fa-solid fa-print"></i>
        <h1>Acompanhamento da Senha</h1>
        <p>Acompanhe seu atendimento em tempo real</p>
    </header>

    <!-- MODO 1: EMISSÃƒO (Mobile Mode) -->
    <section id="view-emissao" style="display:none;" class="view-emissao animate__animated animate__fadeIn">
        <h2 style="margin-bottom:20px; font-weight:800; color:var(--accent-blue);">Retire sua Senha</h2>
        <div id="lista-servicos">
            <!-- Injetado via JS -->
        </div>
    </section>

    <!-- MODO 2: ACOMPANHAMENTO (NOC Mode) -->
    <section id="view-acompanhar" style="display:none;" class="animate__animated animate__fadeIn">

        <!-- Tabs de Multi-Senha (Suporte a 2 senhas) -->
        <div id="multi-ticket-tabs" class="multi-ticket-tabs" style="display:none;"></div>

        <!-- CARD PRINCIPAL (Branco - Fiel Ã  Foto) -->
        <div class="ticket-card">
            <img src="https://cdn-icons-png.flaticon.com/512/263/263142.png" class="ticket-icon-top" alt="Shop">

            <div id="ticket-number" class="ticket-number">---</div>

            <div class="status-badge">
                <div class="status-dot"></div>
                <span id="ticket-status-label">Você é o próximo da fila</span>
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <label><i class="fa-solid fa-users"></i> Pessoas</label>
                    <b id="stat-posicao">0</b>
                </div>
                <div class="stat-box">
                    <label><i class="fa-regular fa-clock"></i> Tempo</label>
                    <b id="stat-tempo">0 min</b>
                </div>
                <div class="stat-box">
                    <label><i class="fa-solid fa-door-open"></i> Guichê</label>
                    <b id="stat-guiche">--</b>
                </div>
            </div>

            <!-- ALERTA DE CHAMADA (Neon Overlay) -->
            <div id="call-overlay" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(24,201,100,0.95); border-radius:25px; color:black; display:flex; flex-direction:column; justify-content:center; align-items:center; z-index:10;">
                 <h2 style="font-weight:900; font-size:24px; margin-bottom:10px;">🔔 CHAMANDO!</h2>
                 <p style="font-weight:bold;">DIRIJA-SE AO</p>
                 <h1 id="overlay-guiche" style="font-size:48px; font-weight:900;">GUICHÊ --</h1>
            </div>
        </div>

        <!-- ÃREA DE PROMOÃ‡Ã•ES (Vermelha - Fiel Ã  Foto) -->
        <section class="promo-section animate__animated animate__fadeInUp">
            <div class="promo-client-header">
                <img src="<?= htmlspecialchars($promo_logo) ?>" class="promo-client-logo" alt="Logo Cliente">
            </div>

            <div class="promo-banner-offer">
                <span>⚡ OFERTAS IMPERDÍVEIS!</span>
                <span style="font-size:10px;">OS MELHORES PREÇOS PERTO DE VOCÊ!</span>
            </div>

            <div id="promo-display" class="promo-content">
                <img id="promo-img" src="" class="promo-img">
                <div class="promo-info">
                    <h4 id="promo-title">...</h4>
                    <div id="promo-price" class="promo-price">---</div>
                    <p id="promo-desc" style="font-size:12px;"></p>
                </div>
            </div>

            <div class="promo-footer">
                Aproveite nossas ofertas.
            </div>
        </section>

        <div style="padding:0 20px 20px;">
            <button onclick="BT.live.novoTicket()" class="bt-button" style="width:100%; background:var(--sidebar); color:white; border:1px solid var(--border);">
                <i class="fa-solid fa-plus"></i> Retirar outra senha
            </button>
        </div>

    </section>

    <!-- FOOTER BRANDÃƒOTECH -->
    <footer class="bt-footer">
        <p>TECNOLOGIA POR</p>
        <img src="http://api.brandaotech.com.br:8080/uploads/logo/logo.png" alt="BrandÃ£o Tech">
        <p style="font-size:9px; margin-top:10px; opacity:0.6;">Tecnologias que conectam, soluçães que transformam.</p>
    </footer>

</div>

<script src="../assets/js/api.js"></script>
<script src="assets/js/engine_v2.js"></script>

</body>
</html>
