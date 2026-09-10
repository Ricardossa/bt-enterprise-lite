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
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
    <title>Retirar Senha - <?= htmlspecialchars($empresa) ?></title>
    <link rel="stylesheet" href="assets/css/premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>

<div class="live-container">
    <header class="header-premium">
        <?php
            $logoUrl = null;
            if (isset($config['promo_logo']) && !empty($config['promo_logo'])) {
                $filename = basename($config['promo_logo']);
                $logoUrl = $baseUrl . 'uploads/' . $filename;
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
            <!-- ... (conteúdo de fechado) ... -->
        <?php else: ?>
            <!-- ETAPA 1: SERVIÇOS -->
            <section id="step-services" class="premium-card">
                <h2 class="premium-card-title">Olá! Seja Bem-vindo</h2>
                <div id="lista-servicos">
                    <div style="padding: 40px; text-align: center;">
                        <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 30px; color: var(--secondary);"></i>
                        <p style="color: var(--text2); margin-top: 15px;">Sincronizando...</p>
                    </div>
                </div>

                <!-- BOTÃO DE CHECK-IN MOBILE (v6.3) -->
                <div id="area-checkin" style="margin-top: 25px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 25px;">
                    <div id="checkin-init">
                        <button onclick="BT.emitter.showCheckin()" class="bt-button" style="width: 100%; background: rgba(255, 193, 7, 0.1); color: var(--warning); border: 1px solid var(--warning); padding: 15px; font-weight: bold; border-radius: 12px;">
                            <i class="fa-solid fa-calendar-check"></i> JÁ TENHO AGENDAMENTO
                        </button>
                    </div>

                    <div id="checkin-form" class="hidden animate__animated animate__fadeIn">
                        <p style="color: var(--text2); font-size: 13px; margin-bottom: 15px;">Digite seu nome ou token para confirmar presença:</p>
                        <input type="text" id="input-query" class="form-control" placeholder="Seu nome ou código..." style="margin-bottom: 15px; background: rgba(0,0,0,0.2); border-color: var(--border); color: #fff;">
                        <div style="display:flex; gap: 10px;">
                            <button onclick="BT.emitter.hideCheckin()" class="bt-button" style="flex:1; background: transparent; border: 1px solid var(--border);">CANCELAR</button>
                            <button id="btn-do-checkin" onclick="BT.emitter.doCheckin()" class="bt-button bt-primary" style="flex:2;">CONFIRMAR CHEGADA</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ETAPA 2: PRIORIDADE (v7.0.3 Mobile) -->
            <section id="step-priority" class="premium-card hidden animate__animated animate__fadeInRight">
                <h2 class="premium-card-title">Tipo de Atendimento</h2>
                <p style="color:var(--text2); font-size:14px; margin-bottom:20px;">Você possui direito a atendimento prioritário (Lei 10.048)?</p>

                <button onclick="BT.emitter.emitir('NORMAL')" class="btn-premium-service" style="border-color: var(--primary); margin-bottom:15px; height:100px;">
                    <span style="font-size:30px;">📋</span> <div>NORMAL</div>
                </button>

                <button onclick="BT.emitter.emitir('PRIORITARIO')" class="btn-premium-service" style="border-color: var(--warning); height:100px;">
                    <span style="font-size:30px;">♿</span> <div>PRIORITÁRIO</div>
                </button>

                <button onclick="BT.emitter.backToServices()" class="bt-button" style="width:100%; margin-top:30px; background:transparent; border:1px solid var(--border);">← VOLTAR</button>
            </section>
        <?php endif; ?>
    </main>

    <footer class="footer-signature">
        <p>Desenvolvido por</p>
        <img src="http://api.brandaotech.com.br/uploads/logo/logo.png" alt="Brandão Tech">
    </footer>
</div>

<script src="../assets/js/api.js?v=7.5"></script>
<script src="assets/js/emitter.js?v=7.5"></script>
</body>
</html>
