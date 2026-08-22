<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

// [SaaS] Resolve o Tenant Autenticado
$tenantId = Auth::tenantId();
if ($tenantId <= 0) {
    die("<h1>❌ Unidade não identificada via subdomínio.</h1>");
}

$config = [];
try {
    $linhas = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE tenant_id = ?", [$tenantId]);
    foreach ($linhas as $linha) { $config[$linha['chave']] = $linha['valor']; }
} catch (Exception $e) {}

$empresa = $config['empresa'] ?? 'Brandão Tech';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento - <?= htmlspecialchars($empresa) ?></title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body { background: #081421; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 15px; font-family: sans-serif; margin: 0; }
        .agenda-card { background: #132238; width: 100%; max-width: 450px; border-radius: 25px; border: 1px solid #1E3552; box-shadow: 0 20px 50px rgba(0,0,0,0.5); padding: 20px; text-align: center; }

        @media (max-width: 480px) {
            .agenda-card { padding: 15px; border-radius: 20px; }
            h2 { font-size: 20px; }
            .slot-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; }
            .slot-item { padding: 10px; font-size: 13px; }
            .bt-button { padding: 15px !important; font-size: 14px !important; }
        }

        .item-list { background: #0D1B2A; border: 1px solid #1E3552; padding: 12px; border-radius: 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: 0.2s; width: 100%; text-align: left; color: #fff; position: relative; box-sizing: border-box; }
        .item-list:hover { border-color: #1DB4FF; background: rgba(29, 180, 255, 0.05); }
        .item-list.active { border-color: #18C964; background: rgba(24, 201, 100, 0.05); }

        .badge-price { background: rgba(29, 180, 255, 0.1); color: #1DB4FF; padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 800; }

        .footer-total { position: sticky; bottom: -20px; background: #132238; padding: 15px 0; border-top: 1px solid #1E3552; margin-top: 15px; }
        .total-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .total-box b { font-size: 20px; color: #18C964; }

        .hidden { display: none !important; }
        .form-control { background: #0D1B2A; border: 1px solid #1E3552; color: #fff; padding: 12px; border-radius: 12px; width: 100%; margin-top: 8px; box-sizing: border-box; }

        .slot-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 15px; }
        .slot-item { background: #0D1B2A; padding: 10px; border-radius: 10px; border: 1px solid #1E3552; cursor: pointer; font-weight: bold; font-size: 14px; }
        .slot-item.active { background: #F5A623; color: #000; border-color: #F5A623; }

        #ticket-qr img { max-width: 130px; height: auto; border-radius: 8px; margin: 0 auto; display: block; }
        #pix-qr img { max-width: 150px; height: auto; border-radius: 10px; margin: 0 auto; display: block; }
    </style>
</head>
<body>

<div class="agenda-card animate__animated animate__fadeIn">
    <header style="margin-bottom: 30px;">
        <h2 style="font-weight: 900; margin: 0; color: #1DB4FF;"><?= htmlspecialchars($empresa) ?></h2>
        <p style="color: #94a3b8; font-size: 13px;">Agendamento Online Premium</p>
    </header>

    <!-- ETAPA 1: ESCOLHA DO PROFISSIONAL -->
    <div id="step-1">
        <h3 style="font-size: 16px; margin-bottom: 20px; text-align: left;"><i class="fa-solid fa-user-tie"></i> 1. Escolha seu Barbeiro</h3>
        <div id="lista-barbeiros">
            <p>Carregando profissionais...</p>
        </div>
    </div>

    <!-- ETAPA 2: ESCOLHA DOS SERVIÇOS -->
    <div id="step-2" class="hidden">
        <h3 style="font-size: 16px; margin-bottom: 20px; text-align: left;"><i class="fa-solid fa-scissors"></i> 2. O que vamos fazer hoje?</h3>
        <div id="lista-servicos">
            <!-- Injetado via JS -->
        </div>

        <div class="footer-total">
            <div class="total-box">
                <span>Total dos Serviços:</span>
                <b id="display-total">R$ 0,00</b>
            </div>
            <button onclick="nextStep(3)" id="btnConfirmarServicos" class="bt-button bt-primary" style="width: 100%; padding: 18px; font-weight: 900;" disabled>PROSSEGUIR</button>
        </div>
    </div>

    <!-- ETAPA 3: DATA E HORA -->
    <div id="step-3" class="hidden">
        <h3 style="font-size: 16px; margin-bottom: 20px; text-align: left;"><i class="fa-solid fa-clock"></i> 3. Escolha o Horário</h3>
        <input type="date" id="data-agenda" class="form-control" min="<?= date('Y-m-d') ?>">
        <div id="lista-slots" class="slot-grid"></div>
    </div>

    <!-- ETAPA 4: IDENTIFICAÇÃO -->
    <div id="step-4" class="hidden">
        <h3 style="font-size: 16px; margin-bottom: 20px; text-align: left;"><i class="fa-solid fa-id-card"></i> 4. Seus Dados</h3>
        <input type="text" id="nome_cliente" class="form-control" placeholder="Seu Nome">
        <input type="tel" id="whatsapp" class="form-control" placeholder="Seu WhatsApp">
        <button id="btnFinalizar" class="bt-button bt-success" style="width: 100%; margin-top: 30px; padding: 18px; font-weight: 900;">CONFIRMAR RESERVA</button>
    </div>

    <!-- ETAPA 5: SUCESSO (v3.0.0) -->
    <div id="step-success" class="hidden">
        <div style="padding: 0;">
            <i class="fa-solid fa-circle-check" style="font-size: 44px; color: #18C964; margin-bottom: 12px;"></i>
            <h2 style="color: #fff; margin-bottom: 5px; font-size: 20px;">Reserva Confirmada!</h2>
            <p style="color: #94a3b8; font-size: 13px;">Seu horário foi reservado com sucesso.</p>

            <div style="background: #0D1B2A; padding: 15px; border-radius: 20px; margin-top: 15px; border: 1px solid #1E3552; position: relative;">
                <span style="font-size: 9px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Código de Atendimento</span>
                <div id="resumo-token" style="font-size: 24px; font-weight: bold; color: #F5A623; margin: 5px 0; letter-spacing: 2px;">--------</div>

                <div style="margin: 10px 0; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <span id="resumo-servicos" style="font-size:12px; color:#fff; font-weight:bold; text-align:left; flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-right:10px;">Serviços</span>
                        <b id="resumo-total" style="color:var(--success); font-size:14px;">R$ 0,00</b>
                    </div>
                    <div style="display:flex; justify-content:center; gap:20px; border-top:1px solid rgba(255,255,255,0.05); padding-top:8px;">
                        <div style="text-align:center;">
                            <span style="font-size:9px; color:var(--text3); display:block;">HORA</span>
                            <b id="resumo-horario" style="font-size: 16px; color: #fff;">--:--</b>
                        </div>
                        <div style="text-align:center;">
                            <span style="font-size:9px; color:var(--text3); display:block;">DATA</span>
                            <b id="resumo-data" style="font-size: 16px; color: #fff;">--/--/--</b>
                        </div>
                    </div>
                </div>

            <!-- v3.5.5: QR Code vs Botão Inteligente (Mobile Detection) -->
            <div id="tracking-action-box" style="margin-top:15px; text-align:center;">
                <div id="qr-desktop-only">
                    <div style="background:#fff; padding:8px; border-radius:12px; display:inline-block; border: 2px solid var(--secondary);">
                        <div id="ticket-qr"></div>
                    </div>
                    <small style="color:#000; font-weight:900; display:block; margin-top:4px; font-size:9px;">ESCANEIE PARA FILA</small>
                </div>
                <div id="btn-mobile-only" class="hidden">
                    <a id="btnGoTrack" href="#" class="bt-button bt-primary" style="width:100%; padding:20px !important; font-weight:900; font-size:16px !important; display:flex; align-items:center; justify-content:center; gap:10px; border-radius:15px; text-decoration:none;">
                        <i class="fa-solid fa-mobile-screen-button" style="font-size:24px;"></i> 📱 ACOMPANHAR MINHA VEZ
                    </a>
                </div>
            </div>

            <a id="btnCancelarReserva" href="#" style="margin-top:12px; font-size:10px; color:#ff4d4d; display:block; text-decoration:underline; opacity:0.6;">
                    Cancelar este agendamento
                </a>
            </div>

            <!-- BLOCO PIX (v3.0.0: Otimizado para Mobile) -->
            <div id="pix-container" class="hidden" style="margin-top:15px; background:rgba(255,255,255,0.02); padding:15px; border-radius:20px; border:1px dashed var(--border);">
                <p style="color:var(--warning); font-size:11px; font-weight:bold; margin-bottom:10px;">⚡ PAGUE O PIX E AGILIZE SEU ATENDIMENTO</p>
                <div id="pix-qr-box" style="background:#fff; padding:8px; border-radius:12px; display:inline-block; margin-bottom:10px;">
                    <div id="pix-qr"></div>
                </div>
                <button id="btnCopyPix" class="bt-button" style="width:100%; background:rgba(29, 180, 255, 0.1); border:1px solid var(--secondary); color:var(--secondary); font-size:11px; margin-bottom:8px; padding:12px !important;">COPIAR CÓDIGO PIX</button>

                <button id="btnNotificarPagamento" class="bt-button" style="width:100%; background:#25D366; color:#000; font-weight:bold; border:none; padding:12px !important; font-size:12px !important;">
                    <i class="fa-brands fa-whatsapp"></i> JÁ PAGUEI / ENVIAR PRINT
                </button>
            </div>

            <!-- v2.9.0: Compartilhamento de Comprovante Digital (v3.0.0: Movido para baixo) -->
            <div style="margin-top:15px;">
                <button id="btnShareZap" class="bt-button" style="width:100%; background:#25D366; color:#000; font-weight:bold; border:none; padding:12px; border-radius:12px; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fa-brands fa-whatsapp" style="font-size:18px;"></i> ENVIAR SENHA PARA WHATSAPP
                </button>
            </div>

            <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
                <button onclick="location.reload()" class="bt-button" style="background: transparent; border: 1px solid #1E3552; padding: 12px !important; font-size:12px !important; opacity:0.5;">REALIZAR OUTRO AGENDAMENTO</button>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
    const state = {
        operador_id: null,
        servicos: [],
        servicos_nomes: [],
        total: 0,
        data: null,
        hora: null,
        nome: '',
        whatsapp: '',
        cliente_uuid: localStorage.getItem('bt_loyalty_uuid') || localStorage.getItem('bt_cliente_uuid') || null
    };

    // [LITE v3.5.0] Integração com Identidade Permanente
    async function checkLoyalty() {
        if (!state.cliente_uuid) return;
        try {
            const res = await fetch(`api/v1/cliente.php?uuid=${state.cliente_uuid}`);
            const json = await res.json();
            if (json.success) {
                state.nome = json.cliente.nome;
                state.whatsapp = json.cliente.whatsapp;

                // Pré-preenche se os campos existirem
                const elNome = document.getElementById('nome_cliente');
                const elZap = document.getElementById('whatsapp');
                if (elNome) elNome.value = state.nome;
                if (elZap) elZap.value = state.whatsapp;

                console.log("💎 Perfil de fidelidade identificado: " + state.nome);
            }
        } catch (e) {}
    }

    if (!state.cliente_uuid) {
        state.cliente_uuid = 'CLI-' + Math.random().toString(36).substr(2, 9).toUpperCase();
        localStorage.setItem('bt_cliente_uuid', state.cliente_uuid);
    }

    async function loadBarbeiros() {
        const res = await fetch('api/v1/agenda.php?action=listar_barbeiros');
        const json = await res.json();
        const container = document.getElementById('lista-barbeiros');
        container.innerHTML = json.data.map(b => {
            const foto = b.foto_url ? `uploads/${b.foto_url.replace('uploads/', '')}` : '';
            const avatar = foto ? `<img src="${foto}" style="width:45px; height:45px; border-radius:50%; object-fit:cover;">` : `🧔`;

            return `
            <div class="item-list" onclick="selectBarbeiro(${b.id}, '${b.nome}')">
                <div style="width:45px; height:45px; background:#1DB4FF; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; overflow:hidden;">
                    ${avatar}
                </div>
                <b>${b.nome}</b>
                <i class="fa-solid fa-chevron-right" style="margin-left:auto; opacity:0.3;"></i>
            </div>
            `;
        }).join('');
    }

    async function selectBarbeiro(id, nome) {
        state.operador_id = id;
        nextStep(2);

        const res = await fetch(`api/v1/agenda.php?action=servicos_por_barbeiro&operador_id=${id}`);
        const json = await res.json();
        const container = document.getElementById('lista-servicos');
        container.innerHTML = json.data.map(s => `
            <div class="item-list" onclick="toggleServico(${s.id}, ${s.preco}, this)">
                <div style="font-size:20px;">${s.icone}</div>
                <div style="flex:1;">
                    <b style="display:block;">${s.nome}</b>
                    <span class="badge-price">R$ ${parseFloat(s.preco).toFixed(2)}</span>
                </div>
                <i class="fa-solid fa-check-circle check-icon hidden" style="color:#18C964;"></i>
            </div>
        `).join('');
    }

    function toggleServico(id, preco, el) {
        const nome = el.querySelector('b').innerText;
        const idx = state.servicos.indexOf(id);

        if (idx > -1) {
            state.servicos.splice(idx, 1);
            state.servicos_nomes.splice(state.servicos_nomes.indexOf(nome), 1);
            state.total -= preco;
            el.classList.remove('active');
            el.querySelector('.check-icon').classList.add('hidden');
        } else {
            state.servicos.push(id);
            state.servicos_nomes.push(nome);
            state.total += preco;
            el.classList.add('active');
            el.querySelector('.check-icon').classList.remove('hidden');
        }
        document.getElementById('display-total').innerText = "R$ " + state.total.toFixed(2);
        document.getElementById('btnConfirmarServicos').disabled = (state.servicos.length === 0);
    }

    async function loadSlots() {
        if (!state.operador_id || !state.data) {
            console.warn("loadSlots: Aguardando seleção de barbeiro e data.");
            return;
        }

        const container = document.getElementById('lista-slots');
        container.innerHTML = '<p style="grid-column: span 3; color: var(--text3);">Buscando horários...</p>';
        try {
            const res = await fetch(`api/v1/agenda.php?operador_id=${state.operador_id}&data=${state.data}`);
            const json = await res.json();

            if (json.success && json.data.length > 0) {
                container.innerHTML = json.data.map(h => `<div class="slot-item" onclick="selectSlot('${h}', this)">${h}</div>`).join('');
            } else {
                const msg = json.message || (state.data === new Date().toISOString().split('T')[0] ? 'Horários de hoje encerrados.' : 'Nenhum horário disponível.');
                container.innerHTML = `<p style="grid-column: span 3; color: var(--warning); padding: 20px;">${msg}</p>`;
            }
        } catch (e) {
            container.innerHTML = `<p style="grid-column: span 3; color: var(--danger); padding:20px;">❌ Falha ao carregar horários. Erro: ${e.message}</p>`;
        }
    }

    function selectSlot(hora, el) {
        state.hora = hora;
        document.querySelectorAll('.slot-item').forEach(i => i.classList.remove('active'));
        el.classList.add('active');
        setTimeout(() => nextStep(4), 300);
    }

    function nextStep(n) {
        [1,2,3,4, 'success'].forEach(s => {
            const el = document.getElementById('step-'+s);
            if(el) el.classList.add('hidden');
        });
        const target = document.getElementById('step-'+n);
        if(target) target.classList.remove('hidden');

        // [LITE v3.3.6] Auto-load slots for today when entering step 3
        if (n === 3) {
            const dateInput = document.getElementById('data-agenda');
            if (!state.data) {
                const hoje = new Date().toISOString().split('T')[0];
                state.data = hoje;
                dateInput.value = hoje;
            }
            loadSlots();
        }
    }

    document.getElementById('data-agenda').onchange = (e) => {
        state.data = e.target.value;
        loadSlots();
    };

    document.getElementById('btnFinalizar').onclick = async () => {
        try {
            state.nome = document.getElementById('nome_cliente').value.trim();
            state.whatsapp = document.getElementById('whatsapp').value.trim();

            if (!state.nome) return alert("Informe seu nome.");

            const btn = document.getElementById('btnFinalizar');
            btn.disabled = true;
            btn.innerText = "RESERVANDO...";

            const res = await fetch('api/v1/agenda.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    servico_ids: state.servicos,
                    operador_id: state.operador_id,
                    nome_cliente: state.nome,
                    whatsapp: state.whatsapp,
                    data: state.data,
                    hora: state.hora,
                    device_id: state.cliente_uuid
                })
            });

            if (!res.ok) throw new Error("Erro na comunicação com o servidor.");

            const json = await res.json();

            if (json.success) {
                // v3.5.6: Salva o ticket no LocalStorage (Prevenção de Falhas)
                try {
                    let saved = localStorage.getItem('bt_premium_tickets');
                    let tickets = [];
                    try { tickets = JSON.parse(saved); if(!Array.isArray(tickets)) tickets = []; } catch(e) { tickets = []; }

                    tickets.push({
                        id: json.id,
                        uuid: json.uuid,
                        senha: 'AGD',
                        status: 'AGENDADO',
                        created_at: new Date().toISOString()
                    });
                    localStorage.setItem('bt_premium_tickets', JSON.stringify(tickets));
                } catch (lsErr) { console.error("LocalStorage Error:", lsErr); }

                // Atualiza resumo visual
                const updateEl = (id, val) => { const el = document.getElementById(id); if(el) el.innerText = val; };
                updateEl('resumo-token', json.token || '---');
                updateEl('resumo-horario', json.horario || '--:--');
                updateEl('resumo-data', json.data || '--/--/--');
                updateEl('resumo-total', "R$ " + (state.total || 0).toFixed(2));

                const sNomes = Array.isArray(state.servicos_nomes) ? state.servicos_nomes.join(' + ') : 'Serviços';
                updateEl('resumo-servicos', sNomes);

                const currentPath = window.location.pathname;
                const basePath = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
                const trackingUrl = window.location.origin + basePath + 'live_premium/acompanhar.php?uuid=' + json.uuid;

                if(document.getElementById('linkAcompanhar')) document.getElementById('linkAcompanhar').href = trackingUrl;
                if(document.getElementById('btnGoTrack')) document.getElementById('btnGoTrack').href = trackingUrl;
                if(document.getElementById('btnCancelarReserva')) document.getElementById('btnCancelarReserva').href = 'cancelar.php?t=' + json.token;

                // [LITE v3.5.6] Detecção de Mobile para UI
                const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
                if (isMobile) {
                    const qrDesktop = document.getElementById('qr-desktop-only');
                    const btnMobile = document.getElementById('btn-mobile-only');
                    if(qrDesktop) qrDesktop.classList.add('hidden');
                    if(btnMobile) btnMobile.classList.remove('hidden');
                }

                const btnZap = document.getElementById('btnShareZap');
                if (btnZap) {
                    btnZap.onclick = () => {
                        const zapNum = state.whatsapp ? state.whatsapp.replace(/\D/g, '') : '';
                        const msg = `✅ *RESERVA CONFIRMADA!*\n\n👤 *Cliente:* ${state.nome}\n📅 *Data:* ${json.data}\n🕒 *Horário:* ${json.horario}\n🔑 *Cód. Atendimento:* ${json.token}\n\n📍 Acompanhe sua vez em tempo real:\n${trackingUrl}`;
                        window.open(`https://wa.me/55${zapNum}?text=${encodeURIComponent(msg)}`);
                    };
                }

                // QR Code Fila
                const qrContainer = document.getElementById('ticket-qr');
                if (qrContainer) {
                    try {
                        const ticketQr = qrcode(0, 'M');
                        ticketQr.addData(trackingUrl);
                        ticketQr.make();
                        qrContainer.innerHTML = ticketQr.createImgTag(4);
                    } catch (e) {
                        qrContainer.innerHTML = `<img src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=${encodeURIComponent(trackingUrl)}" style="width:120px;">`;
                    }
                }

                if (json.pix && document.getElementById('pix-container')) {
                    const pixCode = json.pix.qr_code;
                    const pixQrEl = document.getElementById('pix-qr');
                    if (pixQrEl) {
                        try {
                            const qr = qrcode(0, 'M');
                            qr.addData(pixCode);
                            qr.make();
                            pixQrEl.innerHTML = qr.createImgTag(4);
                        } catch (e) {
                            pixQrEl.innerHTML = `<img src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=${encodeURIComponent(pixCode)}" style="width:160px;">`;
                        }
                    }

                    if (isMobile) {
                        const pixBox = document.getElementById('pix-qr-box') || pixQrEl;
                        if (pixBox) pixBox.style.transform = 'scale(0.8)';
                    }

                    const btnCopy = document.getElementById('btnCopyPix');
                    if (btnCopy) {
                        btnCopy.onclick = () => {
                            const temp = document.createElement("textarea");
                            temp.value = pixCode;
                            document.body.appendChild(temp);
                            temp.select();
                            document.execCommand("copy");
                            document.body.removeChild(temp);
                            alert("Código PIX copiado!");
                        };
                    }

                    const btnNotif = document.getElementById('btnNotificarPagamento');
                    if (btnNotif) {
                        btnNotif.onclick = async () => {
                            await fetch('api/v1/agenda.php?action=confirm_pay', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({ uuid: json.uuid })
                            });
                            const msg = `✅ *PAGAMENTO REALIZADO!*\n\n👤 *Cliente:* ${state.nome}\n💰 *Valor:* R$ ${state.total.toFixed(2)}\n🔑 *Código:* ${json.token}`;
                            window.open(`https://wa.me/5571991077018?text=${encodeURIComponent(msg)}`);
                            alert("Pagamento Notificado!");
                        };
                    }
                    document.getElementById('pix-container').classList.remove('hidden');
                }

                nextStep('success');
            } else {
                alert(json.message);
                const btn = document.getElementById('btnFinalizar');
                btn.disabled = false;
                btn.innerText = "CONFIRMAR RESERVA";
            }
        } catch (err) {
            console.error(err);
            alert("Erro técnico ao processar reserva. Por favor, verifique sua conexão.");
            const btn = document.getElementById('btnFinalizar');
            btn.disabled = false;
            btn.innerText = "CONFIRMAR RESERVA";
        }
    };

    loadBarbeiros();
    checkLoyalty();
</script>

</body>
</html>
