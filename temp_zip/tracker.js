/**
 * BT QUEUE LIVE PREMIUM - MULTI-TRACKER ENGINE v5.1
 * Gerencia o acompanhamento de múltiplas senhas simultâneas.
 */

window.BT = window.BT || {};

BT.tracker = {
    uuids: [],
    promos: [],
    promoIdx: 0,
    polling: null,
    isCalling: false,
    audioUnlocked: false,

    async init() {
        this.updateTicketList();
        this.startPolling();
        this.checkAudioPermission();
        try { await this.loadPromos(); } catch(e) {}
    },

    checkAudioPermission() {
        if (!this.audioUnlocked) {
            document.getElementById('audio-unlock').style.display = 'block';
        }
    },

    unlockAudio() {
        this.audioUnlocked = true;
        document.getElementById('audio-unlock').style.display = 'none';

        // "Acorda" o motor de áudio e fala com um som silencioso
        const ding = new Audio('../assets/audio/ding.mp3');
        ding.volume = 0;
        ding.play().catch(() => {});

        if ('speechSynthesis' in window) {
            const msg = new SpeechSynthesisUtterance('');
            window.speechSynthesis.speak(msg);
        }
    },

    updateTicketList() {
        const saved = localStorage.getItem('bt_premium_tickets');
        let tickets = saved ? JSON.parse(saved) : [];

        // v2.5.0: Filtro de Frescor Diamond (Auto-Purga de senhas velhas)
        const hoje = new Date().toISOString().split('T')[0];
        const ticketsValidos = tickets.filter(t => {
            // Se não tem data (erro raro) ou se a data é de hoje, mantém.
            // Se for de outro dia, apaga para não confundir o cliente.
            if (!t.created_at) return true;
            return t.created_at.startsWith(hoje);
        });

        if (ticketsValidos.length !== tickets.length) {
            console.warn("🧹 Purga de senhas antigas realizada.");
            tickets = ticketsValidos;
            localStorage.setItem('bt_premium_tickets', JSON.stringify(tickets));
        }

        // Se não houver senhas, volta para a tela inicial (Totem)
        if (tickets.length === 0) {
            window.location.href = 'index.php';
            return;
        }

        this.uuids = tickets.map(t => t.cliente_uuid);
    },

    startPolling() {
        this.sync();
        this.polling = setInterval(() => this.sync(), 3000);
    },

    async sync() {
        try {
            const res = await fetch('../api/v1/multi_check.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ uuids: this.uuids })
            });
            const json = await res.json();

            if (json.success) {
                this.renderTickets(json.data);
                this.checkCalls(json.data);

                // --- LÓGICA DE LIMPEZA E SAÍDA (v5.4 Diamond) ---
                const activeTickets = json.data.filter(t => t.status !== 'FINALIZADA');
                const hasFinished = json.data.some(t => t.status === 'FINALIZADA');

                if (activeTickets.length === 0 && hasFinished) {
                    if (!this.exitTimer) {
                        console.log("🏁 Sessão finalizada. Redirecionando em 8s...");
                        this.exitTimer = setTimeout(() => this.finishSession(), 8000);
                    }
                } else if (json.data.length > 0) {
                    this.uuids = json.data.map(t => t.cliente_uuid);
                    localStorage.setItem('bt_premium_tickets', JSON.stringify(json.data));
                }
            }
        } catch (e) { console.error("Multi-Sync Error", e); }
    },

    renderTickets(data) {
        const container = document.getElementById('tickets-container');
        if (!container) return;

        // Ordena por Status: Primeiro as que estão CHAMANDO, depois AGUARDANDO, depois FINALIZADA
        const sortedData = [...data].sort((a, b) => {
            if (a.status === 'CHAMANDO') return -1;
            if (b.status === 'CHAMANDO') return 1;
            return 0;
        });

        container.innerHTML = sortedData.map(t => {
            const isFrozen = (t.status === 'CONGELADA');
            const isCalling = (t.status === 'CHAMANDO');
            const isFinished = (t.status === 'FINALIZADA');

            let statusLabel = 'Em Espera';
            let dotColor = 'var(--secondary)';
            let msg = t.mensagem;

            if (isFrozen) {
                statusLabel = 'Pausada ❄️';
                dotColor = '#1DB4FF';
            } else if (isCalling) {
                statusLabel = 'SUA VEZ! 🔔';
                dotColor = 'var(--success)';
            } else if (isFinished) {
                statusLabel = 'Concluído ✅';
                dotColor = '#94a3b8';
            }

            return `
                <section class="premium-card ${isFrozen ? 'frozen-mode' : ''} ${isCalling ? 'calling-mode' : ''}">
                    <div class="ticket-header-row">
                        <span class="serv-info">${t.icone} ${t.servico}</span>
                        <div class="badge-status">
                            <div class="dot-status" style="background:${dotColor}"></div>
                            <span>${statusLabel}</span>
                        </div>
                    </div>
                    <div class="ticket-white-box">
                        <div class="ticket-number">${t.senha}</div>
                        <p class="ticket-msg">${msg}</p>

                        <div class="grid-stats">
                            <div class="stat-item"><label>Fila</label><b>${t.posicao}</b></div>
                            <div class="stat-item"><label>Espera</label><b>${t.tempo_estimado} min</b></div>
                            <div class="stat-item"><label>Local</label><b style="color:var(--success)">${t.guiche}</b></div>
                        </div>
                    </div>
                </section>
            `;
        }).join('');
    },

    checkCalls(data) {
        const activeCall = data.find(t => t.status === 'CHAMANDO');

        if (activeCall) {
            if (!this.isCalling) {
                this.playAlert(activeCall);
                this.isCalling = true;
            }
        } else {
            this.isCalling = false;
        }
    },

    playAlert(ticket) {
        if (!this.audioUnlocked) return;

        try {
            // 1. Toca o Ding
            const audio = new Audio('../assets/audio/ding.mp3');
            audio.play();

            // 2. Vibração (Mobile)
            if (navigator.vibrate) navigator.vibrate([200, 100, 200, 100, 300]);

            // 3. Voz (Anunciar número)
            if ('speechSynthesis' in window) {
                setTimeout(() => {
                    const text = `Sua vez! Senha ${ticket.senha}, dirigir-se ao ${ticket.guiche}`;
                    const msg = new SpeechSynthesisUtterance(text);
                    msg.lang = 'pt-BR';
                    msg.rate = 0.9;
                    window.speechSynthesis.speak(msg);
                }, 1500);
            }
        } catch(e) { console.error("Audio error", e); }
    },

    finishSession() {
        clearInterval(this.polling);
        localStorage.removeItem('bt_premium_tickets');
        window.location.href = 'index.php?new=1';
    },

    // v2.4.3: Função para voltar à emissão sem perder o contexto de porta
    backToEmitter() {
        window.location.href = 'index.php';
    },

    async loadPromos() {
        const res = await fetch('../api/promocoes.php');
        const json = await res.json();
        if (json.success && json.data.length > 0) {
            this.promos = json.data;
            this.renderPromo();
            setInterval(() => this.renderPromo(), 7000);
        }
    },

    renderPromo() {
        try {
            if (this.promos.length === 0) return;
            const p = this.promos[this.promoIdx];
            const elTitle = document.getElementById('promo-title');
            const elImg = document.getElementById('promo-img');
            const elPrice = document.getElementById('promo-price');
            const elDesc = document.getElementById('promo-desc');

            if(elTitle) elTitle.textContent = p.titulo;
            if(elPrice) elPrice.textContent = p.preco || '';
            if(elDesc) elDesc.textContent = p.descricao || '';
            if(elImg) {
                let img = p.imagem || '';
                if (img && !img.startsWith('http')) {
                    img = img.replace('uploads/promocoes/', '').replace('../', '');
                    elImg.src = '../uploads/promocoes/' + img;
                } else { elImg.src = img; }
                elImg.style.display = 'block';
            }
            this.promoIdx = (this.promoIdx + 1) % this.promos.length;
        } catch(e) {}
    }
};

document.addEventListener('DOMContentLoaded', () => BT.tracker.init());
