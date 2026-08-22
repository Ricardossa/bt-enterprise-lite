<?php
declare(strict_types=1);

$pageTitle = 'Acompanhamento da Senha';

include __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="assets/css/btqueue.css">

<div class="bt-container">

    <div class="bt-card">

        <div class="bt-title">
            📱 Acompanhamento da Senha
        </div>

        <div class="bt-subtitle">
            Atualização em tempo real
        </div>

        <div class="bt-icon" id="icone">
            💊
        </div>

        <div class="bt-number" id="senha">
            ---
        </div>

        <div class="bt-status" id="status">
            Carregando...
        </div>

        <div class="bt-grid">

            <div class="bt-info">
                <h5>👥 Pessoas</h5>
                <span id="posicao">--</span>
            </div>

            <div class="bt-info">
                <h5>⏱ Tempo</h5>
                <span id="tempo">--</span>
            </div>

            <div class="bt-info">
                <h5>📍 Guichê</h5>
                <span id="guiche">--</span>
            </div>

        </div>

    </div>

    <div class="bt-card bt-promo">

        <h4>🎁 Oferta do Dia</h4>

        <h3>Vitamina C</h3>

        <p>20% OFF</p>

        <small>Apresente esta tela no caixa.</small>

    </div>

    <div class="bt-footer">

        BT Queue Enterprise • Atualização automática

    </div>

</div>

<script src="assets/js/api.js"></script>
<script src="assets/js/acompanhar.js"></script>

<?php
include __DIR__ . '/includes/footer.php';
?>
