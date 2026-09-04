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
        this.injectTicketFromUrl(); // [v5.7.0] Injeta UUID da URL no motor de rastreio
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

    injectTicketFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const uuid = urlParams.get('uuid');

        if (uuid) {
            console.log("🔗 Injetando UUID da URL:", uuid);
            const saved = localStorage.getItem('bt_premium_tickets');
            let tickets = saved ? JSON.parse(saved) : [];

            // Verifica se já existe para não duplicar
            const exists = tickets.some(t => t.cliente_uuid === uuid);
            if (!exists) {
                tickets.push({
                    cliente_uuid: uuid,
                    created_at: new Date().toISOString()
                });
                localStorage.setItem('bt_premium_tickets', JSON.stringify(tickets));
            }
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
                // [v5.7.3] Fluxo Unificado: Filtra duplicados e renderiza
                const map = new Map();

                json.data.forEach(t => {
                    const key = t.cliente_uuid;
                    // Prioridade: Não finalizada > Finalizada
                    if (!map.has(key) || (map.get(key).status === 'FINALIZADA' && t.status !== 'FINALIZADA')) {
                        map.set(key, t);
                    }
                });

                const finalTickets = Array.from(map.values()).filter(t => {
                    if (t.status !== 'FINALIZADA') return true;

                    const savedTickets = JSON.parse(localStorage.getItem('bt_premium_tickets') || '[]');
                    const cached = savedTickets.find(st => st.cliente_uuid === t.cliente_uuid && st.finished_at);

                    if (cached) t.finished_at = cached.finished_at;
                    else t.finished_at = Date.now();

                    return (Date.now() - t.finished_at < 120000); // 2 minutos
                });

                this.renderTickets(finalTickets);
                this.checkCalls(finalTickets);

                if (finalTickets.length > 0) {
                    this.uuids = finalTickets.map(t => t.cliente_uuid);
                    localStorage.setItem('bt_premium_tickets', JSON.stringify(finalTickets));

                    // [v5.7.4] Gatilho de Finalização: Se TODAS as senhas na tela estiverem FINALIZADAS
                    const allFinished = finalTickets.every(t => t.status === 'FINALIZADA');

                    if (allFinished) {
                        if (!this.exitTimer) {
                            console.log("🏁 Atendimento concluído. Redirecionando em 10s...");
                            this.exitTimer = setTimeout(() => this.finishSession(), 10000);
                        }
                    } else {
                        // Se uma nova senha aparecer ou o status mudar, cancela o timer de saída
                        if (this.exitTimer) {
                            clearTimeout(this.exitTimer);
                            this.exitTimer = null;
                        }
                    }
                }
            }
        } catch (e) { console.error("Multi-Sync Error", e); }
    },

    renderTickets(data) {
        const container = document.getElementById('tickets-container');
        if (!container) return;

        if (!data || data.length === 0) {
            container.innerHTML = `
                <div style="padding: 60px 20px; text-align: center; color: var(--text3);">
                    <i class="fa-solid fa-ghost" style="font-size: 50px; opacity: 0.2; margin-bottom: 20px; display: block;"></i>
                    <p style="font-size: 14px;">Nenhuma senha ativa para hoje.</p>
                </div>
            `;
            return;
        }

        // v2.8.9: Filtro de exibição - Apenas senhas de HOJE e Ativas (ou recém-finalizadas)
        container.innerHTML = data.sort((a, b) => (a.status === 'CHAMANDO' ? -1 : 1)).map(t => {
            const isCalling = (t.status === 'CHAMANDO');
            const isFinished = (t.status === 'FINALIZADA');

            let statusLabel = 'Em Espera';
            let dotColor = 'var(--secondary)';
            let msg = t.mensagem;

            const isAgendado = t.cancel_token !== null;
            let agdInfo = '';
            if (isAgendado) {
                const data = new Date(t.data_agendamento.replace(' ', 'T'));
                const hora = data.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
                agdInfo = `
                    <div style="margin-top:15px; padding-top:15px; border-top:1px solid rgba(255,255,255,0.05); text-align:left;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:10px; color:var(--text3); text-transform:uppercase;">Horário Reservado</span>
                            <b style="color:#fff; font-size:14px;">${hora}</b>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:5px;">
                            <span style="font-size:10px; color:var(--text3); text-transform:uppercase;">Token de Cancelamento</span>
                            <b style="color:var(--warning); font-size:13px; letter-spacing:1px;">${t.cancel_token}</b>
                        </div>
                        <a href="../cancelar.php?t=${t.cancel_token}" style="font-size:10px; color:var(--danger); display:block; margin-top:8px; text-decoration:underline; opacity:0.6;">Desejo cancelar este horário</a>
                    </div>
                `;
            }

            if (isFinished) {
                statusLabel = 'Concluído ✅';
                dotColor = '#94a3b8';
            } else if (isCalling) {
                statusLabel = 'SUA VEZ! 🔔';
                dotColor = 'var(--success)';
            }

            return `
                <section class="premium-card ${isCalling ? 'calling-mode' : ''}">
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
                        ${agdInfo}
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
        window.location.href = 'fim.php';
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
