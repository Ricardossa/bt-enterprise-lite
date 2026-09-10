/**
 * BT QUEUE LOYALTY ENGINE v1.2 (Diamond Dynamic)
 * Gerencia a identidade permanente do cliente e sistema de pontos.
 */

window.Loyalty = {
    api: '../api/v1/cliente.php',
    client: null,

    init() {
        const uuid = localStorage.getItem('bt_loyalty_uuid');
        if (uuid) {
            // [v1.1.5] Sincroniza Identidade com o APK se estiver em Iframe
            if (window.self !== window.top) {
                window.parent.postMessage({ type: 'bt_login', uuid: uuid }, '*');
            }
            this.fetchProfile(uuid);
        } else {
            this.showView('register');
        }
    },

    async fetchProfile(uuid) {
        try {
            const res = await fetch(`${this.api}?uuid=${uuid}`);
            const json = await res.json();

            if (json.success) {
                console.log("✅ Perfil carregado:", json.cliente.nome);
                if (json.agendamento) console.log("📅 Agendamento detectado:", json.agendamento);

                this.client = json.cliente;
                this.renderProfile(json);
                this.showView('profile');
            } else {
                localStorage.removeItem('bt_loyalty_uuid');
                this.showView('register');
            }
        } catch (e) {
            console.error("Erro ao buscar perfil", e);
            this.showView('register');
        }
    },

    renderProfile(data) {
        const c = data.cliente;
        const fid = data.fidelidade;
        const config = fid.config;

        document.getElementById('welcome-title').innerText = "Olá, " + c.nome.split(' ')[0] + "!";
        document.getElementById('welcome-subtitle').innerText = "Bem-vindo de volta";
        document.getElementById('user-points').innerText = fid.saldo || 0;

        const meta = config.meta_pontos || 10;
        const premio = config.premio_desc || "Corte de Cabelo Grátis";

        document.getElementById('reward-name').innerText = premio;

        if (config.ativo == 1) {
            const faltam = meta - (fid.saldo % meta);
            if (fid.saldo > 0 && fid.saldo % meta === 0) {
                document.getElementById('reward-rules').innerText = `PARABÉNS! Você completou ${meta} pontos e ganhou seu prêmio!`;
                document.getElementById('reward-rules').style.color = "var(--success)";
            } else {
                document.getElementById('reward-rules').innerText = `Faltam ${faltam} atendimentos para o prêmio.`;
                document.getElementById('reward-rules').style.color = "";
            }
        } else {
            document.getElementById('reward-rules').innerText = "O programa de pontos está temporariamente pausado.";
            document.getElementById('reward-rules').style.color = "var(--text3)";
        }

        // [v3.0.0] Lógica de Check-in Automático
        const banner = document.getElementById('checkin-banner');

        if (data.agendamento) {
            if (data.agendamento.status === 'AGENDADO') {
                document.getElementById('checkin-hora').innerText = data.agendamento.hora;

                // [v1.2.1] Exibe o nome do barbeiro se houver
                const barbeiroMsg = data.agendamento.barbeiro_nome ? ` com <b>${data.agendamento.barbeiro_nome.split(' ')[0]}</b>` : '';
                document.getElementById('checkin-hora').innerHTML = `${data.agendamento.hora}${barbeiroMsg}`;

                banner.classList.remove('hidden');
            } else if (data.agendamento.status === 'PRESENTE' || data.agendamento.status === 'CHAMANDO') {
                banner.classList.add('hidden');
                // Se já está presente, redireciona para acompanhar a fila e ver promoções
                this.showActiveTicket(data.agendamento.uuid);
            }
        } else {
            banner.classList.add('hidden');
        }

        // [v1.2.0] Carrega Planos do Clube
        this.loadPlans();
    },

    async loadPlans() {
        const container = document.getElementById('container-planos');
        if (!container) return;

        try {
            // Verifica se já tem assinatura
            const resAtiva = await fetch(`../api/v1/clube_gestao.php?action=ver_assinatura_ativa&cliente_id=${this.client.id}`);
            const jsonAtiva = await resAtiva.json();

            if (jsonAtiva.success) {
                container.innerHTML = `
                    <div style="background:rgba(24, 201, 100, 0.05); border:1px solid var(--success); padding:15px; border-radius:15px; text-align:left;">
                        <b style="color:var(--success); display:block; font-size:16px;">VOP! VOCÊ É ${jsonAtiva.data.plano_nome.toUpperCase()}</b>
                        <p style="margin:5px 0 0; font-size:12px; opacity:0.8;">Você possui <b>${jsonAtiva.data.cortes_restantes} cortes</b> disponíveis até ${jsonAtiva.data.data_fim}.</p>
                    </div>
                `;
                return;
            }

            // Se não tem, lista planos para adesão
            const res = await fetch('../api/v1/clube_gestao.php?action=listar_planos');
            const json = await res.json();

            if (json.success && json.data.length > 0) {
                container.innerHTML = json.data.map(p => `
                    <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border); padding:15px; border-radius:15px; display:flex; justify-content:space-between; align-items:center;">
                        <div style="text-align:left;">
                            <b style="color:#fff; display:block;">${p.nome}</b>
                            <small style="color:var(--text3);">${p.qtd_cortes} cortes por mês</small>
                            <b style="color:var(--warning); display:block; margin-top:5px;">R$ ${parseFloat(p.preco).toFixed(2)}</b>
                        </div>
                        <button onclick="Loyalty.solicitarAssinatura(${p.id}, '${p.nome}')" class="bt-button" style="padding:10px 15px; font-size:11px; background:var(--warning); color:#000; border:none; border-radius:8px; font-weight:bold;">
                            QUERO ESTE
                        </button>
                    </div>
                `).join('');
            } else {
                document.getElementById('clube-section').classList.add('hidden');
            }
        } catch (e) { console.error("Erro ao carregar planos", e); }
    },

    async solicitarAssinatura(planoId, planoNome) {
        if (!confirm(`Deseja solicitar a adesão ao plano "${planoNome}"?\nSua solicitação será analisada pelo barbeiro.`)) return;

        try {
            const res = await fetch('../api/v1/clube_gestao.php?action=solicitar_assinatura', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cliente_id: this.client.id,
                    plano_id: planoId
                })
            });
            const json = await res.json();
            alert(json.message);
            this.loadPlans();
        } catch (e) { alert("Erro ao enviar solicitação."); }
    },

    async doCheckin() {
        const uuid = localStorage.getItem('bt_loyalty_uuid');
        const btn = document.getElementById('btn-do-checkin');

        if (!uuid) return alert("Erro: Perfil não identificado.");

        btn.disabled = true; btn.innerText = "CONFIRMANDO...";

        try {
            const res = await fetch('../api/v1/agenda.php?action=checkin', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cliente_uuid: uuid })
            });
            const json = await res.json();

            if (json.success) {
                document.getElementById('checkin-banner').classList.add('hidden');

                // Feedback tátil se disponível (Capacitor)
                if (window.Capacitor && window.Capacitor.Plugins.Haptics) {
                    window.Capacitor.Plugins.Haptics.impact({ style: 'heavy' });
                }

                // Redireciona para a tela de acompanhamento oficial (Fila + Promoções)
                this.showActiveTicket(json.data.uuid);
            } else {
                alert(json.message);
                btn.disabled = false; btn.innerText = "CONFIRMAR MINHA CHEGADA";
            }
        } catch (e) {
            alert("Erro ao realizar check-in.");
            btn.disabled = false; btn.innerText = "CONFIRMAR MINHA CHEGADA";
        }
    },

    showActiveTicket(uuid) {
        window.location.href = '../live_premium/acompanhar.php?uuid=' + uuid;
    },

    async registrar() {
        const nome = document.getElementById('reg-nome').value.trim();
        const whatsapp = document.getElementById('reg-whatsapp').value.trim();
        const nascimento = document.getElementById('reg-nascimento').value;

        if (!nome || !whatsapp) return alert("Por favor, preencha Nome e WhatsApp.");

        const btn = document.getElementById('btn-save');
        btn.disabled = true; btn.innerText = "CRIANDO PERFIL...";

        try {
            const res = await fetch(this.api, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nome, whatsapp, data_nascimento: nascimento
                })
            });
            const json = await res.json();

            if (json.success) {
                localStorage.setItem('bt_loyalty_uuid', json.cliente.uuid);
                localStorage.setItem('bt_cliente_uuid', json.cliente.uuid);
                this.fetchProfile(json.cliente.uuid);
            } else {
                alert(json.message);
                btn.disabled = false; btn.innerText = "CRIAR MEU PERFIL DIGITAL";
            }
        } catch (e) {
            alert("Falha ao conectar com o servidor.");
            btn.disabled = false;
        }
    },

    async recover() {
        const whatsapp = document.getElementById('rec-whatsapp').value.trim();
        if (!whatsapp) return alert("Informe seu WhatsApp.");

        const btn = document.getElementById('btn-recover');
        btn.disabled = true; btn.innerText = "BUSCANDO...";

        try {
            const res = await fetch(`${this.api}?action=buscar_whatsapp`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ whatsapp })
            });
            const json = await res.json();

            if (json.success) {
                localStorage.setItem('bt_loyalty_uuid', json.cliente.uuid);
                localStorage.setItem('bt_cliente_uuid', json.cliente.uuid);
                this.fetchProfile(json.cliente.uuid);
            } else {
                alert(json.message || "Cadastro não encontrado.");
                btn.disabled = false; btn.innerText = "BUSCAR MEU PERFIL";
            }
        } catch (e) {
            alert("Erro de conexão.");
            btn.disabled = false;
        }
    },

    goService() {
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('t');
        const uuid = localStorage.getItem('bt_loyalty_uuid');

        if (token) {
            window.location.href = '../live_premium/index.php?t=' + token;
        } else if (uuid) {
            window.location.href = '../app_cliente/index.html';
        } else {
            window.location.href = '../live_premium/index.php';
        }
    },

    logout() {
        if (confirm("Deseja sair da sua conta neste celular?")) {
            localStorage.removeItem('bt_loyalty_uuid');
            localStorage.removeItem('bt_cliente_uuid');
            location.reload();
        }
    },

    showView(view) {
        document.getElementById('loading-view').classList.add('hidden');
        document.getElementById('register-view').classList.add('hidden');
        document.getElementById('profile-view').classList.add('hidden');
        document.getElementById('recovery-view').classList.add('hidden');

        const el = document.getElementById(`${view}-view`);
        if (el) el.classList.remove('hidden');
    },

    showRecovery() { this.showView('recovery'); },
    showRegister() { this.showView('register'); }
};

document.addEventListener('DOMContentLoaded', () => Loyalty.init());
