/**
 * BT QUEUE LIVE PREMIUM - EMITTER ENGINE (V4.2 - UTF8 FIX)
 * Lógica para escolha de serviço e redirecionamento.
 */

window.BT = window.BT || {};

console.log("🚀 DIAMOND v7.5 - EMITTER ENGINE ACTIVATED");

BT.emitter = {
    currentToken: '',
    deviceUuid: '',

    async init() {
        // --- GESTÃO DE IDENTIDADE ÚNICA (UUID PERSISTENTE) ---
        this.deviceUuid = localStorage.getItem('bt_device_uuid');
        if (!this.deviceUuid) {
            this.deviceUuid = typeof crypto.randomUUID === 'function'
                ? crypto.randomUUID()
                : 'dev-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('bt_device_uuid', this.deviceUuid);
        }

        // --- CAPTURA DE TOKEN DE SEGURANÇA ---
        const urlParams = new URLSearchParams(window.location.search);
        this.currentToken = urlParams.get('t') || '';

        // --- MASTER RESET: Limpa tudo se houver reset=1 ou new=1 na URL ---
        if (window.location.search.includes('reset=1') || window.location.search.includes('new=1')) {
            localStorage.removeItem('bt_premium_tickets');
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
        }

        // --- GESTÃO DE MÚLTIPLAS SENHAS (PLATFORM DIAMOND) ---
        const saved = localStorage.getItem('bt_premium_tickets');
        let tickets = saved ? JSON.parse(saved) : [];

        // v2.5.0: Filtro de Frescor (Purga senhas de dias anteriores)
        const hoje = new Date().toISOString().split('T')[0];
        tickets = tickets.filter(t => !t.created_at || t.created_at.startsWith(hoje));
        localStorage.setItem('bt_premium_tickets', JSON.stringify(tickets));

        const isMultiTicketEnabled = window.BT_MOBILE_CONFIG?.multi_ticket !== false;

        if (!isMultiTicketEnabled && tickets.length >= 1) {
            // Se o plano NÃO permite multi-senhas, redireciona direto se já tiver uma
            window.location.href = 'acompanhar.php?uuid=' + tickets[0].cliente_uuid;
            return;
        }

        await this.loadServices();
    },

    async loadServices() {
        const list = document.getElementById('lista-servicos');
        try {
            const res = await fetch('../api/servicos.php');
            const json = await res.json();
            if (json.success) {
                list.innerHTML = '';

                // v2.4.2: Filtro de Ocultação Premium Blindado (Só senhas VIVAS ocultam botões)
                const saved = localStorage.getItem('bt_premium_tickets');
                const tickets = saved ? JSON.parse(saved) : [];
                const activeServiceIds = tickets
                    .filter(t => ['AGUARDANDO','CHAMANDO','CONGELADA'].includes(t.status))
                    .map(t => parseInt(t.servico_id))
                    .filter(id => !isNaN(id));

                console.log("Serviços Ocultos (Ativos):", activeServiceIds);

                json.data.forEach(s => {
                    const idAtual = parseInt(s.id);
                    if (activeServiceIds.includes(idAtual)) {
                        console.log("PREMIUM: Ocultando serviço ativo:", s.nome);
                        return; // Pula este serviço
                    }

                    const btn = document.createElement('button');
                    btn.className = 'btn-premium-service';
                    btn.style.borderColor = s.cor || 'var(--primary)';
                    btn.innerHTML = `<span>${s.icone}</span> <div>${s.nome}</div>`;
                    btn.onclick = () => BT.emitter.selectService(s.id);
                    list.appendChild(btn);
                });

                if (list.innerHTML === '') {
                    list.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text2); font-size:14px;">Você já possui senhas ativas para todos os serviços.</div>';
                }
            }
        } catch (e) {
            console.error("Erro API:", e);
            list.innerHTML = '<p style="color:var(--text2);">Falha ao carregar os serviços de atendimento.</p>';
        }
    },

    // --- MÓDULO DE PRIORIDADE MOBILE (v7.0.5 Diamond) ---
    selectedServiceId: null,

    selectService(id) {
        console.log("Mobile: Serviço selecionado:", id);
        BT.emitter.selectedServiceId = id;

        // --- BYPASS DE PRIORIDADE (v7.4 Diamond) ---
        if (window.BT_MOBILE_CONFIG?.priority_selection === '0') {
            console.log("Mobile: Triagem desativada. Emitindo Normal...");
            BT.emitter.emitir('NORMAL');
            return;
        }

        // Sincronia de Telas Diamond (v7.0.6)
        document.getElementById('step-services').classList.add('hidden');
        document.getElementById('step-priority').classList.remove('hidden');

        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    backToServices() {
        BT.emitter.selectedServiceId = null;
        document.getElementById('step-priority').classList.add('hidden');
        document.getElementById('step-services').classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    async emitir(tipo = 'NORMAL') {
        const id = BT.emitter.selectedServiceId;
        if (!id) {
            alert("Erro: Selecione um serviço.");
            BT.emitter.backToServices();
            return;
        }

        const btns = document.querySelectorAll('#step-priority button');
        btns.forEach(b => b.disabled = true);

        try {
            const res = await fetch('../api/senhas.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    servico_id: id,
                    device_id: this.deviceUuid,
                    t: this.currentToken,
                    tipo: tipo
                })
            });
            const json = await res.json();
            if (json.success) {
                // v2.4.1: Garante o servico_id na gravação inicial para ocultação imediata
                const novoTicket = json.data;
                if (!novoTicket.servico_id) novoTicket.servico_id = id;

                // Adiciona a nova senha ao pool local sem apagar as outras
                let tickets = JSON.parse(localStorage.getItem('bt_premium_tickets') || '[]');
                tickets = tickets.filter(t => t.id !== novoTicket.id);
                tickets.push(novoTicket);
                localStorage.setItem('bt_premium_tickets', JSON.stringify(tickets));

                window.location.href = 'acompanhar.php?uuid=' + novoTicket.cliente_uuid;
            } else {
                alert("Erro: " + json.message);
                BT.emitter.backToServices();
            }
        } catch (e) { alert("Falha de conexão ao emitir senha."); }
        btns.forEach(b => b.disabled = false);
    },

    // --- LÓGICA DE CHECK-IN MOBILE (v6.3 Diamond) ---
    showCheckin() {
        document.getElementById('checkin-init').classList.add('hidden');
        document.getElementById('checkin-form').classList.remove('hidden');
        document.getElementById('input-query').focus();
    },

    hideCheckin() {
        document.getElementById('checkin-init').classList.remove('hidden');
        document.getElementById('checkin-form').classList.add('hidden');
    },

    async doCheckin() {
        const query = document.getElementById('input-query').value.trim();
        if (!query) return alert("Por favor, digite seu nome ou token.");

        const btn = document.getElementById('btn-do-checkin');
        btn.disabled = true; btn.innerText = "PROCESSANDO...";

        try {
            const res = await fetch('../api/v1/agenda.php?action=checkin', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ query: query })
            });
            const json = await res.json();

            if (json.success) {
                // Adiciona a senha agendada ao pool local para acompanhamento
                let tickets = JSON.parse(localStorage.getItem('bt_premium_tickets') || '[]');
                tickets = tickets.filter(t => t.id !== json.data.id);
                tickets.push(json.data);
                localStorage.setItem('bt_premium_tickets', JSON.stringify(tickets));

                // Redireciona para a tela de acompanhamento (Visão de Fila)
                window.location.href = 'acompanhar.php?uuid=' + json.data.cliente_uuid;
            } else {
                alert(json.message || "Agendamento não encontrado para hoje.");
                btn.disabled = false; btn.innerText = "CONFIRMAR CHEGADA";
            }
        } catch (e) {
            alert("Falha de comunicação com o servidor.");
            btn.disabled = false; btn.innerText = "CONFIRMAR CHEGADA";
        }
    }
};

document.addEventListener('DOMContentLoaded', () => BT.emitter.init());
