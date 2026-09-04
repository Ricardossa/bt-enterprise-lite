<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;

Auth::iniciar();
if (!Auth::autenticado()) {
    header('Location: login.php');
    exit;
}

$operadorLogado = Auth::operador();
$isAdmin = Auth::isAdmin();

// Se for Admin, exibe tela de escolha (Intersticial)
if ($isAdmin && !isset($_GET['modo_auditoria'])) {
    $pageTitle = 'Acesso Restrito';
    include __DIR__ . '/includes/header.php';
    ?>
    <div style="max-width: 500px; margin: 100px auto; text-align: center; background: var(--card); padding: 40px; border-radius: 20px; border: 1px solid var(--border);" class="animate__animated animate__fadeIn">
        <i class="fa-solid fa-shield-halved" style="font-size: 60px; color: var(--warning); margin-bottom: 25px;"></i>
        <h2 style="font-weight: 900;">Acesso Restrito</h2>
        <p style="color: var(--text2); line-height: 1.6;">Você está logado como <b>Administrador</b>. O Painel de Punho é exclusivo para o uso de <b>Operadores e Barbeiros</b>.</p>

        <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 30px;">
            <a href="logout.php" class="btn-call-next" style="height: 60px; text-decoration: none; background: var(--danger); box-shadow: none;">
                <span>SAIR E LOGAR COMO OPERADOR</span>
            </a>
            <a href="?modo_auditoria=1" class="bt-button" style="text-decoration: none; text-align: center; background: var(--sidebar); border: 1px solid var(--border);">
                <i class="fa-solid fa-eye"></i> CONTINUAR COMO ADMIN (AUDITORIA)
            </a>
            <hr style="opacity: 0.05; margin: 20px 0;">
            <button onclick="window.close()" style="background:transparent; border:none; color: var(--text3); font-size: 13px; font-weight: bold; cursor:pointer;">
                <i class="fa-solid fa-times"></i> FECHAR ESTA ABA E VOLTAR
            </button>
        </div>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}
$servicos = Database::fetchAll("SELECT id, nome FROM servicos WHERE ativo = 1 ORDER BY nome ASC");

$pageTitle = 'Operador de Bolso';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --bg: #081421;
            --card: #132238;
            --sidebar: #0D1B2A;
            --primary: #1565C0;
            --secondary: #1DB4FF;
            --accent: #1DB4FF;
            --border: #1E3552;
            --success: #18C964;
            --warning: #F5A623;
            --danger: #FF4D4D;
            --text: #FFFFFF;
            --text2: #94a3b8;
            --text3: #64748b;
        }

        body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; padding: 0; min-height: 100vh; overflow-x: hidden; }

        .barber-container { max-width: 500px; margin: 0 auto; padding: 20px; display: flex; flex-direction: column; min-height: 100vh; box-sizing: border-box; }

        .barber-header { text-align: center; margin-bottom: 30px; padding-bottom: 10px; border-bottom: 1px solid var(--border); }
        .barber-header h1 { font-size: 18px; margin: 0; font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: 1px; }
        .barber-header .badge { display: inline-block; margin-top: 8px; font-size: 10px; background: var(--primary); padding: 4px 12px; border-radius: 50px; font-weight: 900; }

        .queue-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 20px; text-align: center; position: relative; overflow: hidden; }
        .stat-card b { font-size: 36px; display: block; color: var(--secondary); line-height: 1; }
        .stat-card span { font-size: 11px; text-transform: uppercase; color: var(--text2); font-weight: 800; letter-spacing: 1px; margin-top: 8px; display: block; }
        .stat-card.fin { background: linear-gradient(135deg, #132238 0%, #0D1B2A 100%); border-color: var(--success); grid-column: span 2; }
        .stat-card.fin b { color: var(--success); }
        .stat-card.fin .eye-toggle { position: absolute; top: 15px; right: 20px; color: var(--text3); cursor: pointer; font-size: 18px; transition: .2s; }
        .stat-card.fin .eye-toggle:hover { color: #fff; }
        .stat-card.fin.hidden-value b { filter: blur(8px); opacity: 0.3; }

        .barber-status-bar { display: flex; gap: 10px; margin-bottom: 25px; }
        .status-pill { flex: 1; padding: 12px; border-radius: 12px; border: 2px solid var(--border); background: var(--card); color: var(--text3); font-weight: bold; font-size: 12px; cursor: pointer; text-align: center; transition: .2s; text-transform: uppercase; }
        .status-pill.active.online { border-color: var(--success); color: var(--success); background: rgba(24, 201, 100, 0.05); }
        .status-pill.active.break { border-color: var(--warning); color: var(--warning); background: rgba(245, 166, 35, 0.05); }

        .btn-call-next { width: 100%; height: 140px; border-radius: 25px; background: linear-gradient(135deg, var(--primary) 0%, #0012CC 100%); border: none; color: #fff; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 15px 30px rgba(0,25,255,0.3); transition: 0.2s; }
        .btn-call-next:active { transform: scale(0.95); }
        .btn-call-next i { font-size: 40px; }
        .btn-call-next span { font-size: 22px; font-weight: 900; letter-spacing: 1px; }

        /* CARD DE ATENDIMENTO ATUAL (DIAMOND STYLE) */
        .active-ticket-card { background: #fff; border-radius: 30px; padding: 35px 20px; margin-bottom: 30px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.3); border: 3px solid var(--secondary); }
        .active-ticket-card label { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 2px; }
        .active-ticket-card .senha-num { font-size: 80px; font-weight: 900; color: var(--primary); line-height: 1; margin: 10px 0; letter-spacing: -2px; }
        .active-ticket-card .cliente-nome { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 20px; background: #f1f5f9; padding: 10px; border-radius: 12px; display: block; }

        .op-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-op { padding: 18px; border-radius: 15px; border: none; font-weight: 900; font-size: 14px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-op:active { transform: scale(0.95); }
        .btn-op-success { background: var(--success); color: #fff; grid-column: span 2; }
        .btn-op-warning { background: var(--warning); color: #000; }
        .btn-op-danger { background: var(--danger); color: #fff; }

        .next-list { background: rgba(255,255,255,0.03); border-radius: 25px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); }
        .next-list h3 { font-size: 13px; margin: 0 0 20px; color: var(--text3); text-transform: uppercase; font-weight: 900; letter-spacing: 2px; }
        .next-item { display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .next-item:last-child { border: none; }
        .next-item .idx { width: 35px; height: 35px; background: rgba(29, 180, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; color: var(--secondary); font-size: 14px; }
        .next-item .info { flex: 1; }
        .next-item .info b { display: block; font-size: 16px; color: #fff; }
        .next-item .info span { font-size: 12px; color: var(--text2); }

        .setup-screen { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: var(--bg); z-index: 2000; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; }
        .hidden { display: none !important; }
        .bt-footer { margin-top: auto; padding: 30px 0; text-align: center; opacity: 0.2; }
        .bt-footer img { height: 18px; }
    </style>
</head>
<body>

    <div class="barber-container">
        <header class="barber-header">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <?php if($isAdmin): ?>
                    <a href="dashboard.php" style="color:var(--text3); text-decoration:none; font-size:12px; font-weight:bold; background:rgba(255,255,255,0.05); padding:8px 12px; border-radius:10px; border:1px solid var(--border);">
                        <i class="fa-solid fa-house"></i> VOLTAR
                    </a>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <h1 style="font-size: 16px; flex:1; text-align:center;"><?= htmlspecialchars($operadorLogado['nome']) ?></h1>

                <div style="display:flex; gap:10px;">
                    <button onclick="Barber.showSetupMobile()" style="color:var(--secondary); background:rgba(29, 180, 255, 0.1); border:1px solid var(--secondary); padding:8px 12px; border-radius:10px; font-size:12px; font-weight:bold; cursor:pointer;">
                        <i class="fa-solid fa-mobile-screen"></i> APP
                    </button>
                    <a href="logout.php" style="color:var(--danger); text-decoration:none; font-size:12px; font-weight:bold; background:rgba(255,77,77,0.1); padding:8px 15px; border-radius:10px; border:1px solid rgba(255,77,77,0.2);">
                        <i class="fa-solid fa-right-from-bracket"></i> SAIR
                    </a>
                </div>
            </div>
            <span id="service-badge" class="badge">CARREGANDO...</span>
        </header>

        <div class="queue-stats">
            <div id="card-ganhos" class="stat-card fin animate__animated animate__pulse animate__infinite">
                <i id="toggle-privacy" class="fa-solid fa-eye-slash eye-toggle" onclick="Barber.togglePrivacy()"></i>
                <b id="total-ganhos">R$ 0,00</b>
                <span>Seu Rendimento Hoje</span>
                <!-- Barra de Rendimento Individual (v3.0.0 Elite) -->
                <div style="width:100%; height:4px; background:rgba(255,255,255,0.1); border-radius:10px; margin-top:15px; overflow:hidden;">
                    <div id="bar-rendimento" style="width:0%; height:100%; background:var(--success); box-shadow:0 0 10px var(--success); transition:1s;"></div>
                </div>
            </div>
            <div class="stat-card">
                <b id="total-fila">0</b>
                <span>Na Fila</span>
            </div>
            <div class="stat-card">
                <b id="total-agenda" style="color: var(--warning);">0</b>
                <span>Agendados</span>
            </div>
        </div>

        <div class="barber-status-bar">
            <div id="pill-online" class="status-pill active online" onclick="Barber.setStatus('ONLINE')">
                <i class="fa-solid fa-circle-check"></i> Disponível
            </div>
            <div id="pill-break" class="status-pill break" onclick="Barber.setStatus('BREAK')">
                <i class="fa-solid fa-mug-hot"></i> Almoço/Pausa
            </div>
        </div>

        <div style="margin-bottom: 25px;">
            <button onclick="Barber.openBalconyModal()" class="bt-button" style="width:100%; height:60px; background:var(--sidebar); border:1px solid var(--border); border-radius:15px; font-weight:bold; display:flex; align-items:center; justify-content:center; gap:10px;">
                <i class="fa-solid fa-user-plus" style="color:var(--secondary);"></i> CLIENTE DE BALCÃO
            </button>
        </div>

        <div id="view-free" class="animate__animated animate__fadeIn">
            <button onclick="Barber.chamarProximo()" class="btn-call-next">
                <i class="fa-solid fa-bell"></i>
                <span>CHAMAR PRÓXIMO</span>
            </button>
        </div>

        <div id="view-busy" class="hidden">
            <!-- ... (conteudo mantido) ... -->
            <div class="active-ticket-card animate__animated animate__zoomIn">
                <label>Atendendo agora</label>
                <div id="current-code" class="senha-num">---</div>
                <span id="current-name" class="cliente-nome">Cliente não identificado</span>
                <p id="current-servico" style="font-size: 13px; color: var(--text2); margin-top: -10px; font-weight: 800; text-transform: uppercase;">SERVIÇO: ---</p>

                <div class="op-actions">
                    <button id="btnMarkPaid" onclick="Barber.marcarComoPago()" class="btn-op" style="grid-column: span 2; background:#F5A623; color:#000;">
                        <i class="fa-solid fa-money-bill-1-wave"></i> CONFIRMAR PAGAMENTO (DINHEIRO/PIX)
                    </button>
                    <button onclick="Barber.finalizar()" class="btn-op btn-op-success">
                        <i class="fa-solid fa-check-double"></i> FINALIZAR ATENDIMENTO
                    </button>
                    <button onclick="Barber.rechamar()" class="btn-op btn-op-warning">
                        <i class="fa-solid fa-bullhorn"></i> RECHAMAR
                    </button>
                </div>
            </div>
        </div>

        <!-- NOVO: LISTA DE AGENDADOS (v2.7.2) -->
        <section class="next-list animate__animated animate__fadeInUp" style="margin-bottom:20px; border-color:var(--warning);">
            <h3 style="color:var(--warning);"><i class="fa-solid fa-calendar-day"></i> Agenda do Dia</h3>
            <div id="list-agenda">
                <p style="color: var(--text3); text-align: center; font-size: 13px; padding: 15px;">Sem compromissos.</p>
            </div>
        </section>

        <section class="next-list animate__animated animate__fadeInUp">
            <h3><i class="fa-solid fa-list-ul" style="color:var(--secondary); margin-right:8px;"></i> Próximos na Fila</h3>
            <div id="list-next">
                <p style="color: var(--text3); text-align: center; font-size: 13px; padding: 20px;">Fila vazia.</p>
            </div>
        </section>

        <!-- NOVO: HISTÓRICO DE PAGAMENTOS (v3.1.0) -->
        <section id="view-payments" class="next-list hidden animate__animated animate__fadeInUp" style="margin-top:20px; border-color:var(--success);">
            <h3 style="color:var(--success);"><i class="fa-solid fa-money-bill-check"></i> Meus Pagamentos</h3>
            <div id="list-payments">
                <p style="color: var(--text3); text-align: center; font-size: 13px; padding: 15px;">Sem registros.</p>
            </div>
        </section>

        <!-- NOVO: HISTÓRICO DE CHAMADAS (v2.7.6) -->
        <section class="next-list animate__animated animate__fadeInUp" style="margin-top:20px; border-color:rgba(255,255,255,0.02);">
            <h3 style="opacity:0.6; font-size:11px;"><i class="fa-solid fa-history"></i> Chamadas Recentes</h3>
            <div id="list-history" style="opacity:0.5;">
                <!-- Injetado via JS -->
            </div>
        </section>

        <footer class="bt-footer">
            <p style="font-size: 10px; font-weight: 800; letter-spacing: 2px; margin-bottom: 5px;">POWERED BY</p>
            <img src="https://api.brandaotech.com.br/uploads/logo/logo.png" alt="BT">
        </footer>
    </div>

    <!-- MODAL CLIENTE DE BALCÃO (v3.3.0) -->
    <div id="modal-balcony" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:9999; align-items:center; justify-content:center; padding:20px;">
        <div class="stat-card" style="max-width:400px; background:var(--card); border:2px solid var(--secondary); padding:30px; text-align:left;">
            <h2 style="font-size:18px; margin-bottom:10px; color:#fff;">➕ Novo Cliente de Balcão</h2>
            <p style="font-size:12px; color:var(--text2); margin-bottom:20px;">Para clientes que chegaram direto na cadeira.</p>

            <div class="form-group">
                <label style="font-size:11px; color:var(--text3); text-transform:uppercase;">Nome do Cliente</label>
                <input type="text" id="balcony-name" class="form-control" style="background:var(--sidebar); border:1px solid var(--border); color:#fff; padding:12px; border-radius:10px; width:100%; margin-top:5px; box-sizing:border-box;" placeholder="Ex: Marcio Ricardo">
            </div>

            <div class="form-group" style="margin-top:15px;">
                <label style="font-size:11px; color:var(--text3); text-transform:uppercase;">Serviço</label>
                <select id="balcony-service" class="form-control" style="background:var(--sidebar); border:1px solid var(--border); color:#fff; padding:12px; border-radius:10px; width:100%; margin-top:5px; box-sizing:border-box;">
                    <?php foreach($servicos as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; gap:10px; margin-top:30px;">
                <button onclick="document.getElementById('modal-balcony').style.display='none'" class="btn-op" style="flex:1; background:var(--sidebar); color:#fff; border:1px solid var(--border);">CANCELAR</button>
                <button onclick="Barber.emitirBalcao()" id="btnSaveBalcony" class="btn-op btn-op-success" style="flex:2;">LANÇAR NA FILA</button>
            </div>
        </div>
    </div>

    <!-- MODAL DE PAREAMENTO MOBILE (ELITE LITE) -->
    <div id="modal-app" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:9999; align-items:center; justify-content:center; padding:20px;">
        <div class="stat-card" style="max-width:350px; background:var(--card); border:2px solid var(--secondary); padding:30px;">
            <h2 style="font-size:18px; margin-bottom:10px;">Conectar Aplicativo</h2>
            <p style="font-size:12px; color:var(--text2); margin-bottom:25px;">Abra o App <b>BT Barber</b> no seu celular e escaneie este código para configurar seu acesso.</p>

            <div id="qrcode-area" style="background:#fff; padding:15px; border-radius:15px; display:inline-block; margin-bottom:20px;"></div>

            <div style="background:rgba(0,0,0,0.3); padding:10px; border-radius:10px; font-size:11px; color:var(--secondary); font-family:monospace; margin-bottom:25px;">
                URL: <span id="debug-url">--</span>
            </div>

            <button onclick="document.getElementById('modal-app').style.display='none'" class="btn-op btn-op-success" style="width:100%;">FECHAR</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script src="assets/js/api.js?v=4.7"></script>
    <script>
        window.Barber = {
            serviceId: <?= (int)$operadorLogado['servico_id'] ?: 'null' ?>,
            guicheId: <?= (int)$operadorLogado['guiche_id'] ?: '1' ?>,
            currentId: null,
            lastFilaCount: 0,

            init() {
                this.updateUI();
                this.startPolling();
                this.loadPrivacy();
            },

            togglePrivacy() {
                const card = document.getElementById('card-ganhos');
                const icon = document.getElementById('toggle-privacy');
                const isHidden = card.classList.toggle('hidden-value');
                icon.className = isHidden ? 'fa-solid fa-eye eye-toggle' : 'fa-solid fa-eye-slash eye-toggle';
                localStorage.setItem('barber_privacy', isHidden ? '1' : '0');
            },

            loadPrivacy() {
                if (localStorage.getItem('barber_privacy') === '1') {
                    this.togglePrivacy();
                }
            },

            async setStatus(status) {
                try {
                    const res = await fetch('api/v1/operador_status.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ status })
                    });
                    const json = await res.json();
                    if (json.success) {
                        document.querySelectorAll('.status-pill').forEach(p => p.classList.remove('active'));
                        if (status === 'ONLINE') document.getElementById('pill-online').classList.add('active');
                        else document.getElementById('pill-break').classList.add('active');
                    }
                } catch(e) { console.error("Erro ao mudar status"); }
            },

            openBalconyModal() {
                document.getElementById('modal-balcony').style.display = 'flex';
                document.getElementById('balcony-name').focus();
            },

            async emitirBalcao() {
                const nome = document.getElementById('balcony-name').value.trim();
                const serviceId = document.getElementById('balcony-service').value;
                const btn = document.getElementById('btnSaveBalcony');

                if (!nome) return alert("Digite o nome do cliente.");

                btn.disabled = true; btn.innerText = "LANÇANDO...";

                try {
                    const res = await fetch('api/senhas.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            servico_id: serviceId,
                            operador_id: <?= (int)$operadorLogado['id'] ?>,
                            nome_cliente: nome,
                            tipo: 'NORMAL'
                        })
                    });
                    const json = await res.json();
                    if (json.success) {
                        document.getElementById('modal-balcony').style.display = 'none';
                        document.getElementById('balcony-name').value = '';
                        this.sync();
                    } else { alert(json.message); }
                } catch(e) { alert("Erro de conexão."); }
                btn.disabled = false; btn.innerText = "LANÇAR NA FILA";
            },

            setService(id, name) {
                this.serviceId = id;
                document.getElementById('setup-screen').classList.add('hidden');
                this.updateUI();
                this.startPolling();
            },

            updateUI() {
                document.getElementById('service-badge').innerText = "CADEIRA: " + (this.serviceId ? this.serviceId : '--');
            },

            showSetupMobile() {
                const modal = document.getElementById('modal-app');
                const qrArea = document.getElementById('qrcode-area');
                const debugUrl = document.getElementById('debug-url');

                // Detecta a URL correta baseada no acesso atual
                const currentUrl = window.location.origin + window.location.pathname.replace('barber_operador.php', '');
                debugUrl.innerText = currentUrl;

                const config = JSON.stringify({
                    protocol: 2,
                    type: 'LITE_CONNECT',
                    url: currentUrl,
                    op_id: this.serviceId,
                    tenant_uuid: '<?= Auth::getCurrentTenant()['uuid'] ?? '' ?>'
                });

                try {
                    const qr = qrcode(0, 'M');
                    qr.addData(config);
                    qr.make();
                    qrArea.innerHTML = qr.createImgTag(6);
                    modal.style.display = 'flex';
                } catch(e) { alert("Erro ao gerar QR"); }
            },

            startPolling() {
                this.sync();
                setInterval(() => this.sync(), 3000);
            },

            async sync() {
                // v2.7.5: No modo Barbeiro Pro, ignoramos o serviceId individual e usamos o menu completo (0)
                try {
                    const res = await BT.api.estado(0, this.guicheId);
                    if (res.success) {
                        const d = res.data;
                        const currentFilaCount = d.fila.length;

                        if (currentFilaCount > this.lastFilaCount) {
                            if (navigator.vibrate) navigator.vibrate([300, 100, 300]);
                        }
                        this.lastFilaCount = currentFilaCount;

                        document.getElementById('total-fila').innerText = currentFilaCount;
                        document.getElementById('total-agenda').innerText = d.agendados.length;

                        const ganhos = parseFloat(d.ganhos_hoje);
                        document.getElementById('total-ganhos').innerText = "R$ " + ganhos.toFixed(2);

                        // Lógica da Barra de Rendimento (Meta sugerida: R$ 500,00)
                        const meta = 500;
                        const porcentagem = Math.min((ganhos / meta) * 100, 100);
                        document.getElementById('bar-rendimento').style.width = porcentagem + "%";

                        // 1. Renderiza Agenda (v2.7.7: Blindado contra null)
                        const agendaList = document.getElementById('list-agenda');
                        if (d.agendados && d.agendados.length > 0) {
                            agendaList.innerHTML = d.agendados.map(a => {
                                let hora = '--:--';
                                if (a.data_agendamento && a.data_agendamento.includes(' ')) {
                                    hora = a.data_agendamento.split(' ')[1].substring(0, 5);
                                }
                                const isPresente = (a.status === 'PRESENTE');
                                return `
                                    <div class="next-item" style="border-left:4px solid ${isPresente ? 'var(--success)' : 'var(--warning)'}; padding-left:10px; margin-bottom:8px;">
                                        <div class="info">
                                            <div style="display:flex; justify-content:space-between;">
                                                <b style="${isPresente ? 'color:var(--success);' : 'color:#fff;'} font-size:14px;">${a.nome_cliente || 'Agendado'}</b>
                                                <div style="display:flex; gap:10px; align-items:center;">
                                                    <b style="color:var(--secondary);">${hora}</b>
                                                    ${a.whatsapp ? `<a href="https://wa.me/55${a.whatsapp.replace(/\D/g, '')}" target="_blank" style="color:#25D366; font-size:16px;"><i class="fa-brands fa-whatsapp"></i></a>` : ''}
                                                </div>
                                            </div>
                                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:3px;">
                                                <span style="font-size:11px; color:var(--text3);">${a.servico_nome || 'Atendimento'}</span>
                                                ${a.barbeiro_nome ? `<span style="font-size:10px; color:var(--secondary); font-weight:bold; text-transform:uppercase;">Prof: ${a.barbeiro_nome.split(' ')[0]}</span>` : ''}
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('');
                        } else {
                            agendaList.innerHTML = '<p style="color: var(--text3); text-align: center; font-size: 13px; padding: 15px;">Sem compromissos.</p>';
                        }

                        if (d.chamando) {
                            this.currentId = d.chamando.id;
                            this.currentUuid = d.chamando.uuid;
                            document.getElementById('current-code').innerText = d.chamando.codigo;
                            document.getElementById('current-name').innerText = d.chamando.nome_cliente || 'Cliente de Porta';

                            // v1.3.1: Exibe o nome do serviço e preço
                            const isVip = (d.chamando.pagamento_status === 'ISENTO');
                            const servicoNome = d.chamando.servico_nome || '---';
                            document.getElementById('current-servico').innerHTML = servicoNome + (isVip ? ' <span style="color:var(--warning); font-weight:900;">[👑 VIP]</span>' : '');

                            // Controle do botão de pagamento
                            const btnPaid = document.getElementById('btnMarkPaid');
                            if (btnPaid) {
                                btnPaid.style.display = (d.chamando.pagamento_status === 'PAGO') ? 'none' : 'flex';
                            }

                            document.getElementById('view-busy').classList.remove('hidden');
                            document.getElementById('view-free').classList.add('hidden');
                        } else {
                            this.currentId = null;
                            this.currentUuid = null;
                            document.getElementById('view-busy').classList.add('hidden');
                            document.getElementById('view-free').classList.remove('hidden');
                        }

                        const list = document.getElementById('list-next');
                        let items = [];

                        // v2.4.0: Consolidacao de Fila Real (Espera + Presenca)
                        if (d.fila) {
                            d.fila.forEach(f => {
                                if (items.length < 10) {
                                    const isPaid = (f.pagamento_status === 'PAGO');
                                    const isVip = (f.pagamento_status === 'ISENTO'); // [v3.0.1] Detecção de Assinante
                                    const isPresent = (f.status === 'PRESENTE');
                                    const isAgd = (f.codigo.startsWith('AGD') || f.tipo_atendimento === 'AGENDAMENTO');

                                    items.push({
                                        codigo: f.codigo,
                                        nome: f.nome_cliente || 'Cliente de Porta',
                                        servicos: f.servico_nome,
                                        valor: parseFloat(f.valor_total).toFixed(2),
                                        statusLabel: isPresent ? 'JÁ CHEGOU ✅' : 'EM ESPERA',
                                        badgePago: isPaid ? '<span class="badge" style="background:rgba(24, 201, 100, 0.1); color:var(--success); font-size:9px; margin-left:5px;">PAGO</span>' : '',
                                        badgeVip: isVip ? '<span class="badge" style="background:rgba(245, 166, 35, 0.1); color:var(--warning); font-size:9px; margin-left:5px;"><i class="fa-solid fa-crown"></i> VIP</span>' : '',
                                        badgeAgd: isAgd ? '<span class="badge" style="background:rgba(245, 166, 35, 0.1); color:var(--warning); font-size:9px; margin-left:5px;">AGENDA</span>' : '',
                                        whatsapp: f.whatsapp
                                    });
                                }
                            });
                        }

                        if (items.length > 0) {
                            list.innerHTML = items.map((t, i) => `
                                <div class="next-item" style="padding:15px 0;">
                                    <span class="idx">${i+1}º</span>
                                    <div class="info">
                                        <b style="font-size:15px;">${t.nome} ${t.badgePago}${t.badgeVip}${t.badgeAgd}</b>
                                        <div style="font-size:12px; color:var(--text2); margin-top:4px;">
                                            ${t.codigo} · <span style="color:var(--secondary); font-weight:bold;">${t.servicos}</span>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                                            <span style="font-size:10px; font-weight:900; letter-spacing:1px; color:var(--text3);">${t.statusLabel}</span>
                                            <div style="display:flex; gap:12px; align-items:center;">
                                                ${t.whatsapp ? `<a href="https://wa.me/55${t.whatsapp.replace(/\D/g, '')}" target="_blank" style="color:#25D366; font-size:16px;"><i class="fa-brands fa-whatsapp"></i></a>` : ''}
                                                <b style="color:var(--success); font-size:14px;">R$ ${t.valor}</b>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            list.innerHTML = '<p style="color: var(--text3); text-align: center; font-size: 13px; padding: 20px;">Ninguém na fila.</p>';
                        }

                        // 3. Histórico de Chamadas (v3.0.2: Tag VIP)
                        const historyList = document.getElementById('list-history');
                        if (historyList && d.historico && d.historico.length > 0) {
                            historyList.innerHTML = d.historico.map(h => {
                                const time = (h.chamada_em && h.chamada_em.includes(' '))
                                            ? h.chamada_em.split(' ')[1].substring(0,5)
                                            : '--:--';
                                const isVip = (h.pagamento_status === 'ISENTO');
                                return `
                                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:12px; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.02);">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <span>${h.senha}</span>
                                            ${isVip ? '<span class="badge" style="background:var(--warning); color:#000; font-size:9px; padding:2px 6px;">👑 VIP</span>' : ''}
                                        </div>
                                        <span style="color:var(--text3);">${time}</span>
                                    </div>
                                `;
                            }).join('');
                        }

                        // 4. Histórico de Pagamentos (v3.1.0)
                        const paymentList = document.getElementById('list-payments');
                        const paymentView = document.getElementById('view-payments');
                        if (paymentList && d.pagamentos && d.pagamentos.length > 0) {
                            paymentView.classList.remove('hidden');
                            paymentList.innerHTML = d.pagamentos.map(p => `
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.02);">
                                    <div>
                                        <b style="color:var(--success); display:block;">R$ ${parseFloat(p.valor).toFixed(2)}</b>
                                        <small style="color:var(--text3); font-size:10px;">REF: ${p.data_inicio.split('-').reverse().join('/')} a ${p.data_fim.split('-').reverse().join('/')}</small>
                                    </div>
                                    <span style="color:var(--text3); font-size:11px;">${p.data_pagamento.split(' ')[0].split('-').reverse().join('/')}</span>
                                </div>
                            `).join('');
                        } else {
                            paymentView.classList.add('hidden');
                        }
                    }
                } catch (e) {}
            },

            async chamarProximo() {
                try {
                    // v2.7.6: Chama o proximo baseado em todas as especialidades do barbeiro (id 0)
                    const res = await BT.api.chamar(0, this.guicheId);
                    if (!res.success) alert(res.message);
                } catch (e) { alert("Falha na conexão."); }
            },

            async rechamar() {
                if (!this.currentId) return;
                try { await BT.api.rechamar(this.currentId); } catch (e) {}
            },

            async marcarComoPago() {
                if (!this.currentUuid) return;
                if (!confirm("Confirmar que recebeu o pagamento deste cliente?")) return;

                try {
                    const res = await fetch('api/v1/agenda.php?action=confirm_pay', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ uuid: this.currentUuid })
                    });
                    const json = await res.json();
                    if (json.success) {
                        BT.toast.sucesso("Pagamento confirmado!");
                        this.sync();
                    }
                } catch(e) { alert("Erro ao confirmar."); }
            },

            async finalizar() {
                if (!this.currentId) return;
                try {
                    const res = await BT.api.finalizar(this.currentId);
                    if (res.success) this.sync();
                } catch (e) {}
            }
        };
        document.addEventListener('DOMContentLoaded', () => Barber.init());
    </script>
</body>
</html>
