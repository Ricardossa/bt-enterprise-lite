/**
 * BT QUEUE TV PREMIUM - CINEMA ENGINE V2 (NATIVE BRIDGE EDITION)
 * Motor de chamadas inteligente com suporte a voz nativa Android e Watchdog.
 */

window.BT = window.BT || {};

BT.tv = {
    internalQueue: [],      // Fila de senhas a serem anunciadas
    isBusy: false,          // Bloqueio enquanto está anunciando
    lastKnownCalls: new Set(), // IDs de chamadas já processadas
    lastHistoryHash: "",    // Controle de mudança do histórico
    isVozHabilitada: true,
    ding: null,             // Objeto de áudio
    watchdogTimer: null,    // Prevenção contra travamento de fila
    isFirstFetch: true,     // Bloqueia voz no primeiro carregamento (F5)

    init() {
        this.updateClock();
        setInterval(() => this.updateClock(), 1000);

        // Prepara o áudio Premium (alert.mp3)
        this.ding = new Audio('assets/audio/alert.mp3');
        this.ding.load();

        // Inicia o motor de polling
        this.fetchState();
        setInterval(() => this.fetchState(), 2000);

        // Inicia o motor de processamento de fila
        this.processQueue();
        setInterval(() => this.processQueue(), 1000);

        console.log("📺 TV Engine Initialized. Native Bridge: " + (typeof AndroidVoz !== 'undefined' ? "SI" : "NO"));
    },

    updateClock() {
        const now = new Date();
        const el = document.getElementById('tv-clock');
        if (el) el.textContent = now.toLocaleTimeString('pt-BR');
    },

    async fetchState() {
        try {
            const res = await fetch('api/estado.php');
            const json = await res.json();
            if (json.success) {
                this.updateUI(json.data);
                this.analyzeNewEvents(json.data.historico);
            }
        } catch (e) { console.error("TV Polling Error:", e); }
    },

    analyzeNewEvents(historico) {
        if (!historico || !Array.isArray(historico)) return;

        [...historico].reverse().forEach(call => {
            const uniqueId = `${call.id}-${call.chamada_em}`;

            if (!this.lastKnownCalls.has(uniqueId)) {
                // Só enfileira para falar se não for a primeira carga (F5)
                if (!this.isFirstFetch) {
                    console.log("📝 Enfileirando nova chamada:", call.senha);
                    this.internalQueue.push(call);
                } else {
                    console.log("🤫 Ignorando voz para histórico inicial:", call.senha);
                }

                this.lastKnownCalls.add(uniqueId);

                if (this.lastKnownCalls.size > 50) {
                    const firstItem = this.lastKnownCalls.values().next().value;
                    this.lastKnownCalls.delete(firstItem);
                }
            }
        });

        // Após a primeira análise, libera a voz para os próximos eventos
        if (this.isFirstFetch) {
            this.isFirstFetch = false;
        }
    },

    async processQueue() {
        if (this.isBusy || this.internalQueue.length === 0) return;

        this.isBusy = true;
        const call = this.internalQueue.shift();

        // Obtém o estado mais recente para pegar o label_cliente
        let data = null;
        try {
            const res = await fetch('api/estado.php');
            const json = await res.json();
            if (json.success) data = json.data;
        } catch(e) {}

        // --- WATCHDOG: Destrava a fila após 12 segundos caso a voz falhe ---
        if (this.watchdogTimer) clearTimeout(this.watchdogTimer);
        this.watchdogTimer = setTimeout(() => {
            if (this.isBusy) {
                console.warn("⚠️ Watchdog: A chamada demorou demais ou a voz falhou. Destravando fila...");
                this.toggleFlashing(false);
                this.isBusy = false;
            }
        }, 12000);

        try {
            this.showCallOnScreen(call, data);

            // 1. IMPACTO CINEMA: Toca Sinal Sonoro e Brilha a Tela
            this.toggleFlashing(true);
            try {
                this.ding.currentTime = 0;
                await this.ding.play();
                // Espera o som "respirar" (2.2 segundos de suspense)
                await new Promise(r => setTimeout(r, 2200));
            } catch(e) { console.warn("Erro ao tocar Áudio: Interação necessária."); }

            // 2. Executa Voz
            await this.speakCall(call, data);

            this.toggleFlashing(false);

        } catch(err) {
            console.error("Erro no processador de chamada:", err);
        } finally {
            clearTimeout(this.watchdogTimer);
            this.isBusy = false;
        }
    },

    showCallOnScreen(call, data) {
        const elSenha = document.getElementById('main-ticket');
        const elGuiche = document.getElementById('main-guiche');
        const elLabel = document.querySelector('.label-chamada');
        const labelBase = data?.label_cliente || 'Paciente';

        if (elSenha) {
            elSenha.textContent = call.nome_cliente || call.senha;

            // --- SMART FONT SIZE (DIAMOND v2) ---
            // Regra mais agressiva para nomes muito longos e preservação de senhas
            const textoExibido = call.nome_cliente || call.senha;
              const len = textoExibido.length;
            if (len <= 4) {
                elSenha.style.fontSize = '320px'; // Aumentado para IMPACTO TOTAL em senhas
            } else if (len <= 12) {
                elSenha.style.fontSize = '200px';
            } else if (len <= 20) {
                elSenha.style.fontSize = '140px';
            } else if (len <= 30) {
                elSenha.style.fontSize = '100px';
            } else {
                elSenha.style.fontSize = '70px'; // Extremo para nomes de 35+ caracteres
            }
        }

        if (elGuiche) elGuiche.textContent = call.guiche_nome || "ATENDIMENTO";

        // Ajusta rótulo hospitalar e PRIORIDADE
        if (elLabel) {
            let texto = call.is_hospital ? `${labelBase} em Atendimento` : 'Senha em Atendimento';
            if (call.tipo_atendimento === 'PREFERENCIAL' || call.tipo_atendimento === 'PRIORITARIO') {
                texto = `⚠️ ${labelBase.toUpperCase()} PREFERENCIAL`;
                document.body.classList.add('priority-alert');
            } else {
                document.body.classList.remove('priority-alert');
            }
            elLabel.textContent = texto;
        }

        elSenha.classList.remove('pulse-ticket');
        void elSenha.offsetWidth;
        elSenha.classList.add('pulse-ticket');
    },

    toggleFlashing(active) {
        const elSenha = document.getElementById('main-ticket');
        if (elSenha) {
            if (active) elSenha.classList.add('calling-now');
            else elSenha.classList.remove('calling-now');
        }
        document.body.classList.toggle('flash-call', active);
    },

    speakCall(call, data) {
        return new Promise((resolve) => {
            if (!this.isVozHabilitada) return resolve();

            const labelBase = data?.label_cliente || 'Paciente';
            const prefixo = labelBase;
            const nomeFalado = call.nome_cliente || call.senha;
              const texto = `${prefixo} ${nomeFalado}, dirigir-se ao ${call.guiche_nome}`;

            // --- CANAL 1: PONTE NATIVA ANDROID (ALTA PERFORMANCE) ---
            if (typeof AndroidVoz !== 'undefined') {
                try {
                    AndroidVoz.cancelar();
                    AndroidVoz.falar(texto);
                    // Como não temos callback de fim da voz nativa, esperamos um tempo fixo
                    setTimeout(resolve, 5000);
                    return;
                } catch (e) { console.error("Erro na ponte AndroidVoz:", e); }
            }

            // --- CANAL 2: BROWSER SPEECH SYNTHESIS (FALLBACK) ---
            window.speechSynthesis.cancel();
            let count = 0;
            const repeat = () => {
                const msg = new SpeechSynthesisUtterance(texto);
                msg.lang = 'pt-BR';
                msg.rate = 0.95;
                msg.onend = () => {
                    count++;
                    if (count < 2) setTimeout(repeat, 800);
                    else resolve();
                };
                msg.onerror = () => resolve(); // Se der erro, pula
                window.speechSynthesis.speak(msg);
            };
            repeat();
        });
    },

    abbreviateName(name) {
        if (!name || name.length <= 18) return name;
        const parts = name.split(' ');
        if (parts.length < 2) return name.substring(0, 18);
        const first = parts[0];
        const last = parts[parts.length - 1];
        return `${first} ${parts[1][0]}. ${last}`; // Ex: ROBERTO D. PRADO
    },

    updateUI(data) {
        const elHistory = document.getElementById('history-list');
        if (!elHistory || !data.historico) return;

        // --- MODO RESTAURAÇÃO (F5): Mostra a última chamada no meio sem piscar ---
        if (this.isFirstFetch && data.historico.length > 0) {
            const lastCall = data.historico[0];
            this.showCallOnScreen(lastCall);
        }

        const currentHash = JSON.stringify(data.historico);
        if (currentHash === this.lastHistoryHash) return;

        this.lastHistoryHash = currentHash;

        elHistory.innerHTML = data.historico.map(h => {
            const isName = h.senha.length > 6;
            const displayName = isName ? this.abbreviateName(h.senha) : h.senha;
            const fontSize = isName ? '24px' : '36px';

            return `
                <li class="history-item animate__animated animate__fadeInRight">
                    <span class="history-ticket" style="font-size: ${fontSize};">${displayName}</span>
                    <span class="history-guiche">${h.guiche_nome}</span>
                </li>
            `;
        }).join('');
    }
};

document.addEventListener('DOMContentLoaded', () => BT.tv.init());
