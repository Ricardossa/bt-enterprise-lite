<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

$config = [];
try {
    $linhas = Database::fetchAll("SELECT chave, valor FROM configuracoes");
    foreach ($linhas as $linha) { $config[$linha['chave']] = $linha['valor']; }
} catch (Exception $e) {}

$empresa = $config['empresa'] ?? 'BT Queue Enterprise';

// CÁLCULO DE BASE_URL PARA O MOBILE (Atomic Path)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . str_replace('live_premium/index.php', '', $_SERVER['SCRIPT_NAME']);

// --- TRAVA DE HORÁRIO MOBILE ---
$abertura = $config['opening_time'] ?? '00:00';
$fechamento = $config['closing_time'] ?? '23:59';
$agora = date('H:i');
$estaFechado = ($agora < $abertura || $agora > $fechamento);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, viewport-fit=cover, maximum-scale=1">
    <title>Retirar Senha - <?= htmlspecialchars($empresa) ?></title>
    <link rel="stylesheet" href="assets/css/premium.css?v=1.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>

<div class="live-container">
    <header class="header-premium">
        <?php
            // [v1.8.6] Logo DinÃ¢mica SaaS
            $logoUrl = $config['logo_url'] ?? $config['promo_logo'] ?? '';
            if (empty($logoUrl) || (!str_starts_with($logoUrl, 'http') && !file_exists(__DIR__ . '/../' . $logoUrl))) {
                $logoUrl = 'https://api.brandaotech.com.br/uploads/logo/logo.png';
            } else {
                $logoUrl = '../' . ltrim($logoUrl, '/');
                $logoUrl .= '?v=' . time(); // Anti-cache
            }
        ?>

        <?php if ($logoUrl): ?>
            <img src="<?= $logoUrl ?>" alt="Logo" style="max-height: 100px; max-width: 80%; object-fit: contain; margin-bottom: 15px;">
        <?php else: ?>
            <i class="fa-solid fa-qrcode"></i>
        <?php endif; ?>

        <script>
            window.BT_MOBILE_CONFIG = {
                multi_ticket: <?= ($config['feature_multi_ticket'] ?? '1') === '1' ? 'true' : 'false' ?>,
                priority_selection: "<?= $config['feature_priority_selection'] ?? '1' ?>"
            };
        </script>

        <?php if ($estaFechado): ?>
            <h1 style="color: #FF4D4D;">LOJA FECHADA</h1>
            <p>Atendimento das <?= $abertura ?> às <?= $fechamento ?></p>
        <?php else: ?>
            <h1>Escolha o Serviço</h1>
            <p>Retire sua senha no seu celular</p>
        <?php endif; ?>
    </header>

    <main>
        <?php if ($estaFechado): ?>
            <section class="premium-card" style="text-align:center; padding:50px 20px;">
                <i class="fa-solid fa-clock-rotate-left" style="font-size:60px; color:var(--danger); margin-bottom:20px;"></i>
                <h2 style="color:#fff;">ATENDIMENTO ENCERRADO</h2>
                <p style="color:var(--text2);">Retorne amanhã a partir das <?= $abertura ?></p>
            </section>
        <?php else: ?>
            <!-- ETAPA 1: ESCOLHA DO PROFISSIONAL (v2.0) -->
            <section id="step-barber" class="premium-card">
                <h2 class="premium-card-title">Com quem deseja ser atendido?</h2>
                <div id="lista-barbeiros">
                    <p style="text-align:center; padding:20px; color:var(--text2);">Carregando profissionais...</p>
                </div>

                <!-- BOTÃO DE CHECK-IN MOBILE (v6.3) -->
                <div id="area-checkin" style="margin-top: 25px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 25px;">
                    <div id="checkin-init">
                        <button onclick="BT.emitter.showCheckin()" class="bt-button" style="width: 100%; background: rgba(255, 193, 7, 0.1); color: var(--warning); border: 1px solid var(--warning); padding: 15px; font-weight: bold; border-radius: 12px;">
                            <i class="fa-solid fa-calendar-check"></i> JÁ TENHO AGENDAMENTO
                        </button>
                    </div>
                </div>
            </section>

            <!-- ETAPA 2: MENU DE SERVIÇOS -->
            <section id="step-services" class="premium-card hidden animate__animated animate__fadeInRight">
                <h2 class="premium-card-title" id="barbeiroNomeTitulo">O QUE VAMOS FAZER?</h2>
                <div id="lista-servicos">
                    <!-- Injetado via JS -->
                </div>

                <div style="margin-top: 30px; background: rgba(0,0,0,0.2); padding: 20px; border-radius: 15px; border: 1px solid var(--border);">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <span style="color:var(--text2); font-size:14px;">TOTAL:</span>
                        <b id="mobileTotalValue" style="font-size: 24px; color: var(--success);">R$ 0,00</b>
                    </div>
                    <button onclick="BT.emitter.confirmarServicos()" id="btnConfirmarMobile" class="bt-button bt-primary" style="width: 100%; padding: 18px; font-weight: 900;" disabled>EMITIR MINHA SENHA</button>
                    <button onclick="BT.emitter.backToBarbers()" class="bt-button" style="width: 100%; margin-top: 15px; background:transparent; border:1px solid var(--border); font-size:12px;">← MUDAR PROFISSIONAL</button>
                </div>
            </section>

            <!-- MODAL DE CHECK-IN -->
            <div id="checkin-form" class="premium-card hidden animate__animated animate__fadeIn">
                <h2 class="premium-card-title">Confirme sua Chegada</h2>
                <p style="color: var(--text2); font-size: 13px; margin-bottom: 15px;">Digite seu nome ou token do agendamento:</p>
                <input type="text" id="input-query" class="form-control" placeholder="Seu nome ou código..." style="margin-bottom: 15px; background: rgba(0,0,0,0.2); border-color: var(--border); color: #fff; width:100%; padding:15px; border-radius:10px;">
                <div style="display:flex; gap: 10px;">
                    <button onclick="BT.emitter.hideCheckin()" class="bt-button" style="flex:1; background: transparent; border: 1px solid var(--border);">CANCELAR</button>
                    <button id="btn-do-checkin" onclick="BT.emitter.doCheckin()" class="bt-button bt-primary" style="flex:2;">CONFIRMAR</button>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer-signature">
        <p>Desenvolvido por</p>
        <img src="https://api.brandaotech.com.br/uploads/logo/logo.png" alt="Brandão Tech">
    </footer>
</div>

<script src="../assets/js/api.js?v=7.8.8"></script>
<script src="assets/js/emitter.js?v=7.8.8"></script>

</body>
</html>
