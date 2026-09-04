/**
 * BT QUEUE LIVE ENGINE V2 - PREMIUM
 * Suporte a Multi-Senha e Visual Fiel à Foto.
 */

window.BT = window.BT || {};

BT.live = {
    tickets: [],
    activeIndex: 0,
    promos: [],
    promoIdx: 0,
    polling: null,
    ultimaSenhaFalada: "",

    async iniciar() {
        const saved = localStorage.getItem('bt_premium_tickets');
        this.tickets = saved ? JSON.parse(saved) : [];

        if (this.tickets.length > 0) {
            this.showView('acompanhar');
            this.renderTabs();
            this.startPolling();
        } else {
            this.showView('emissao');
            this.loadServices();
        }

        this.loadPromos();
    },

    showView(view) {
        document.getElementById('view-emissao').style.display = (view === 'emissao') ? 'block' : 'none';
        document.getElementById('view-acompanhar').style.display = (view === 'acompanhar') ? 'block' : 'none';
    },

    // --- EMISSÃO ---
    async loadServices() {
        const list = document.getElementById('lista-servicos');
        try {
            const res = await fetch('api/servicos.php');
            const json = await res.json();
            if (json.success) {
                list.innerHTML = '';

                // v2.4.2: Filtro de Ocultação Blindado (Só senhas VIVAS ocultam botões)
                const activeServiceIds = this.tickets
                    .filter(t => ['AGUARDANDO','CHAMANDO','CONGELADA'].includes(t.status))
                    .map(t => parseInt(t.servico_id))
                    .filter(id => !isNaN(id));

                console.log("Serviços Ocultos (Ativos):", activeServiceIds);

                json.data.forEach(s => {
                    const idAtual = parseInt(s.id);
                    if (activeServiceIds.includes(idAtual)) {
                        console.log("BOTÃO OCULTO ->", s.nome);
                        return;
                    }

                    const btn = document.createElement('button');
                    btn.className = 'service-btn';
                    btn.style.background = s.cor || '#0019FF';
                    btn.innerHTML = `<span style="font-size:30px;">${s.icone}</span> <div>${s.nome}</div>`;
                    btn.onclick = () => this.emitir(s.id);
                    list.appendChild(btn);
                });

                if (list.innerHTML === '') {
                    list.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text2);">Você já possui senhas para todos os serviços disponíveis.</div>';
                }
            }
        } catch (e) { list.innerHTML = 'Erro ao carregar serviços.'; }
    },

    async emitir(id) {
        if (this.tickets.length >= 2) {
            alert("Você já atingiu o limite de 2 senhas simultâneas.");
            return;
        }

        try {
            const res = await fetch('api/senhas.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ servico_id: id })
            });
            const json = await res.json();
            if (json.success) {
                // v2.3.5: Garante o servico_id na gravação inicial
                const novoTicket = json.data;
                if (!novoTicket.servico_id) novoTicket.servico_id = id;

                this.tickets.push(novoTicket);
                this.activeIndex = this.tickets.length - 1;
                this.saveTickets();
                this.showView('acompanhar');
                this.renderTabs();
                this.startPolling();
            }
        } catch (e) { alert("Falha ao emitir senha."); }
    },

    novoTicket() {
        if (this.tickets.length >= 2) {
             alert("Você já possui 2 senhas em aberto.");
             return;
        }
        this.showView('emissao');
        this.loadServices();
    },

    saveTickets() {
        localStorage.setItem('bt_premium_tickets', JSON.stringify(this.tickets));
    },

    // --- ACOMPANHAMENTO ---
    renderTabs() {
        const container = document.getElementById('multi-ticket-tabs');
        if (this.tickets.length <= 1) {
            container.style.display = 'none';
            return;
        }
        container.style.display = 'flex';
        container.innerHTML = this.tickets.map((t, i) => `
            <div class="ticket-tab ${i === this.activeIndex ? 'active' : ''}" onclick="BT.live.switchTicket(${i})">
                Senha ${t.senha}
            </div>
        `).join('');
    },

    switchTicket(index) {
        this.activeIndex = index;
        this.renderTabs();
        this.updateUI();
    },

    startPolling() {
        if (this.polling) clearInterval(this.polling);
        this.updateAllTickets();
        this.polling = setInterval(() => this.updateAllTickets(), 3000);
    },

    async updateAllTickets() {
        for (let i = 0; i < this.tickets.length; i++) {
            try {
                const res = await fetch('api/acompanhar.php?uuid=' + this.tickets[i].cliente_uuid);
                const json = await res.json();
                if (json.success) {
                    const d = json.data;
                    this.tickets[i].status = d.status;
                    this.tickets[i].posicao = d.posicao;
                    this.tickets[i].tempo = d.tempo_estimado;
                    this.tickets[i].guiche = d.guiche;
                    this.tickets[i].msg = d.mensagem;
                    this.tickets[i].servico_id = parseInt(d.servico_id);

                    this.saveTickets(); // v2.3.4: Sela a memória local

                    if (d.status === 'CHAMANDO' && d.senha !== this.ultimaSenhaFalada) {
                        this.notificar(d);
                    }
                } else {
                    this.tickets.splice(i, 1);
                    this.saveTickets();
                    location.reload();
                }
            } catch (e) {}
        }
        this.updateUI();
    },

    updateUI() {
        const t = this.tickets[this.activeIndex];
        if (!t) return;

        document.getElementById('ticket-number').textContent = t.senha;
        document.getElementById('ticket-status-label').textContent = t.msg;
        document.getElementById('stat-posicao').textContent = t.posicao;
        document.getElementById('stat-tempo').textContent = t.tempo + ' min';
        document.getElementById('stat-guiche').textContent = t.guiche || '--';

        const overlay = document.getElementById('call-overlay');
        if (t.status === 'CHAMANDO') {
            overlay.style.display = 'flex';
            document.getElementById('overlay-guiche').textContent = 'GUICHÊ ' + t.guiche;
        } else {
            overlay.style.display = 'none';
        }

        if (t.status === 'FINALIZADA') {
            setTimeout(() => {
                this.tickets.splice(this.activeIndex, 1);
                this.activeIndex = 0;
                this.saveTickets();
                if (this.tickets.length === 0) window.location.href = '/painel_v4/mobile/fim/';
                else location.reload();
            }, 5000);
        }
    },

    notificar(d) {
        this.ultimaSenhaFalada = d.senha;
        if (navigator.vibrate) navigator.vibrate([300, 100, 300]);
        const voice = new SpeechSynthesisUtterance(`Senha ${d.senha}, dirija-se ao guichê ${d.guiche}`);
        voice.lang = 'pt-BR';
        window.speechSynthesis.speak(voice);
    },

    // --- PROMOS ---
    async loadPromos() {
        try {
            const res = await fetch('api/promocoes.php');
            const json = await res.json();
            if (json.success) {
                this.promos = json.data;
                this.renderPromo();
                setInterval(() => this.renderPromo(), 6000);
            }
        } catch (e) {}
    },

    renderPromo() {
        if (this.promos.length === 0) return;
        const p = this.promos[this.promoIdx];
        document.getElementById('promo-title').textContent = p.titulo;
        document.getElementById('promo-desc').textContent = p.descricao;
        document.getElementById('promo-price').textContent = p.preco || '';

        const img = p.imagem.startsWith('http') ? p.imagem : '/painel_v4/public/uploads/promocoes/' + p.imagem;
        document.getElementById('promo-img').src = img;

        this.promoIdx = (this.promoIdx + 1) % this.promos.length;
    }
};

document.addEventListener('DOMContentLoaded', () => BT.live.iniciar());
