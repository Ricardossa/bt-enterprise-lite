<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

// [SaaS] Resolve o Tenant Autenticado
$tenant = Auth::getCurrentTenant();
if (!$tenant) {
    die("<h1>❌ Unidade não identificada via subdomínio.</h1>");
}

$tenantId = (int)$tenant['id'];

$config = [];
try {
    $linhas = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE tenant_id = ?", [$tenantId]);
    foreach ($linhas as $linha) { $config[$linha['chave']] = $linha['valor']; }
} catch (Exception $e) {}

$empresa = $config['empresa'] ?? 'Barbearia';

// [v3.1.3] LÃ³gica de Logo DinÃ¢mica SaaS (Garante que nÃ£o puxe a da Lite)
$logoUrl = $config['logo_url'] ?? $config['promo_logo'] ?? '';
if (empty($logoUrl) || (!str_starts_with($logoUrl, 'http') && !file_exists(__DIR__ . '/../' . $logoUrl))) {
    $logoUrl = '../assets/img/logo-placeholder.png';
} else {
    $logoUrl = '../' . ltrim($logoUrl, '/');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, viewport-fit=cover, maximum-scale=1">
    <title>Fidelidade - <?= htmlspecialchars($empresa) ?></title>
    <link rel="stylesheet" href="../live_premium/assets/css/premium.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .loyalty-points-box { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); border-radius: 30px; padding: 25px 15px; text-align: center; margin-bottom: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .points-number { font-size: 60px; font-weight: 900; color: #fff; line-height: 1; }
        .points-label { font-size: 12px; font-weight: 800; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; }

        .form-profile { background: var(--card); border-radius: 25px; padding: 20px; border: 1px solid var(--border); }
        .form-profile label { display: block; font-size: 11px; color: var(--text3); margin-bottom: 6px; text-transform: uppercase; font-weight: bold; }
        .form-profile .form-control { background: var(--sidebar); border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 12px; width: 100%; margin-bottom: 15px; box-sizing: border-box; }

        .reward-card { background: rgba(255,255,255,0.03); border: 1px dashed var(--border); border-radius: 20px; padding: 20px; display: flex; align-items: center; gap: 15px; }
        .reward-icon { width: 50px; height: 50px; background: rgba(24, 201, 100, 0.1); color: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    </style>
</head>
<body class="noc-theme">

<div id="loyalty-app" class="live-container">

    <!-- HEADER DINÂMICO -->
    <header class="header-premium">
        <img src="<?= $logoUrl ?>" alt="Logo" style="max-height: 80px; max-width: 80%; object-fit: contain; margin-bottom: 15px;">
        <h1 id="welcome-title">Cartão Fidelidade</h1>
        <p id="welcome-subtitle">Ganhe prêmios exclusivos</p>
    </header>

    <main id="main-content">
        <!-- LOADING INITIAL -->
        <div id="loading-view" style="text-align:center; padding:100px 0;">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:40px; color:var(--secondary);"></i>
            <p style="margin-top:20px; color:var(--text2);">Identificando seu perfil...</p>
        </div>

        <!-- VISÃO 1: CADASTRO / IDENTIFICAÇÃO -->
        <div id="register-view" class="hidden animate__animated animate__fadeInUp">
            <section class="form-profile">
                <h2 style="font-size:20px; margin-bottom:25px; text-align:center;">Crie seu Perfil</h2>

                <label>Nome Completo *</label>
                <input type="text" id="reg-nome" class="form-control" placeholder="Como quer ser chamado?">

                <label>WhatsApp *</label>
                <input type="tel" id="reg-whatsapp" class="form-control" placeholder="(00) 00000-0000">

                <label>Data de Nascimento (Opcional)</label>
                <input type="date" id="reg-nascimento" class="form-control">

                <button onclick="Loyalty.registrar()" id="btn-save" class="bt-button bt-primary" style="width: 100%; padding: 18px; font-weight: 900;">
                    CRIAR MEU PERFIL DIGITAL
                </button>

                <div style="text-align:center; margin-top:20px;">
                    <button onclick="Loyalty.goService()" style="background:transparent; border:1px solid var(--border); color:var(--text3); padding:15px; width:100%; border-radius:12px; font-weight:bold; cursor:pointer;">
                        PULAR E TIRAR SENHA SEM CADASTRO
                    </button>
                </div>

                <div style="text-align:center; margin-top:25px;">
                    <p style="font-size:12px; color:var(--text3);">Já possui cadastro mas trocou de celular?</p>
                    <button onclick="Loyalty.showRecovery()" style="background:transparent; border:none; color:var(--secondary); font-weight:bold; font-size:13px; text-decoration:underline; cursor:pointer;">
                        Recuperar meu acesso
                    </button>
                </div>
            </section>
        </div>

        <!-- VISÃO 2: DASHBOARD DO CLIENTE -->
        <div id="profile-view" class="hidden animate__animated animate__fadeIn">
            <section class="loyalty-points-box">
                <div class="points-number" id="user-points">0</div>
                <div class="points-label">Pontos Acumulados</div>
            </section>

            <!-- BANNER DE CHECK-IN (v3.0: Exibido apenas se houver agendamento hoje) -->
            <div id="checkin-banner" class="hidden animate__animated animate__pulse animate__infinite" style="background: rgba(255, 193, 7, 0.1); border: 2px solid var(--warning); border-radius: 20px; padding: 20px; margin-bottom: 25px; text-align: center;">
                <div style="font-size: 40px; margin-bottom: 10px;">📍</div>
                <h3 style="color: var(--warning); margin: 0 0 5px 0; font-weight: 900;">VOCÊ CHEGOU!</h3>
                <p style="color: var(--text2); font-size: 14px; margin-bottom: 20px;">Vimos que você tem um horário hoje às <b id="checkin-hora">--:--</b>.</p>
                <button onclick="Loyalty.doCheckin()" id="btn-do-checkin" class="bt-button" style="background: var(--warning); color: #000; font-weight: 900; width: 100%; padding: 15px; border-radius: 12px; border: none; cursor: pointer;">
                    CONFIRMAR MINHA CHEGADA
                </button>
            </div>

            <section class="premium-card" style="margin-bottom:25px;">
                <h3 style="font-size:14px; margin-bottom:15px; color:var(--text2); text-transform:uppercase;">🎁 Sua Próxima Recompensa</h3>
                <div class="reward-card">
                    <div class="reward-icon"><i class="fa-solid fa-gift"></i></div>
                    <div>
                        <b style="display:block; color:#fff;" id="reward-name">Carregando prêmio...</b>
                        <small style="color:var(--text3);" id="reward-rules">Complete 10 pontos para ganhar.</small>
                    </div>
                </div>
            </section>

            <section class="premium-card" id="clube-section" style="margin-bottom:25px;">
                <h3 style="font-size:14px; margin-bottom:15px; color:var(--warning); text-transform:uppercase;">👑 Clube de Vantagens</h3>
                <div id="container-planos" style="display:flex; flex-direction:column; gap:10px;">
                    <p style="font-size:12px; color:var(--text3);">Carregando planos disponíveis...</p>
                </div>
            </section>

            <div style="display:flex; flex-direction:column; gap:12px;">
                <button onclick="Loyalty.goService()" class="bt-button bt-primary" style="width:100%; padding:20px; font-weight:900; font-size:18px;">
                    <i class="fa-solid fa-scissors"></i> ESCOLHER SERVIÇO AGORA
                </button>

                <button onclick="Loyalty.logout()" class="bt-button" style="background:transparent; border:1px solid var(--border); font-size:11px; opacity:0.5;">
                    Não é você? Sair da conta
                </button>
            </div>
        </div>

        <!-- VISÃO 3: RECUPERAÇÃO -->
        <div id="recovery-view" class="hidden animate__animated animate__fadeIn">
             <section class="form-profile">
                <h2 style="font-size:20px; margin-bottom:15px; text-align:center;">Recuperar Acesso</h2>
                <p style="text-align:center; font-size:13px; color:var(--text2); margin-bottom:25px;">Informe o seu WhatsApp cadastrado.</p>

                <label>WhatsApp</label>
                <input type="tel" id="rec-whatsapp" class="form-control" placeholder="(00) 00000-0000">

                <button onclick="Loyalty.recover()" id="btn-recover" class="bt-button bt-secondary" style="width: 100%; padding: 18px; font-weight: 900;">
                    BUSCAR MEU PERFIL
                </button>

                <button onclick="Loyalty.showRegister()" style="width:100%; margin-top:15px; background:transparent; border:none; color:var(--text3); font-size:12px;">
                    ← Voltar para cadastro
                </button>
            </section>
        </div>
    </main>

    <footer class="footer-signature">
        <p>Identidade Protegida por</p>
        <img src="https://api.brandaotech.com.br/uploads/logo/logo.png" alt="BT">
    </footer>
</div>

<script src="assets/js/loyalty.js?v=3.1.2"></script>
</body>
</html>
