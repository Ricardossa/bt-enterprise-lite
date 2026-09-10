/**
 * BT QUEUE SMART - MOTOR DE MURAL DINÂMICO v1.9.1
 * Lógica de Carrossel Expandida: Agenda, Promos, Clube, Fidelidade e Fila
 */

window.SMART = {
    currentSlide: 0,
    slides: ['agenda', 'promos', 'clube', 'fidelidade', 'fila'],
    lastCallId: null,
    lastHistoryHash: '', // [v1.8.4] Hash para evitar refresh desnecessário
    isInterrupted: false,
    promoIndex: -1,
    promoData: [],
    clubeIndex: -1, // [v1.9.5] Ãndice para rotaÃ§Ã£o de planos
    clubeData: [], // Cache de Planos
    fidelidadeData: null,

    init() {
        console.log("🚀 BT SMART TV INITIALIZING v2.7.0 (ZOOM COMPLIANT)");
        this.updateClock();
        this.startCarousel();
        this.startSync();
    },

    updateClock() {
        const el = document.getElementById('tv-clock');
        const tick = () => {
            const now = new Date();
            if (el) el.innerText = now.toLocaleTimeString('pt-BR');
        };
        tick();
        setInterval(tick, 1000);
    },

    startCarousel() {
        this.showSlide(0);
        setInterval(() => {
            if (this.isInterrupted) return;

            // Lógica de Rotação Sequencial de Promoções - 10 SEGUNDOS
            const isPromoSlide = this.slides[this.currentSlide] === 'promos';
            if (isPromoSlide && this.promoData.length > 0) {
                this.promoIndex++;
                if (this.promoIndex < this.promoData.length) {
                    this.renderPromoItem(this.promoData[this.promoIndex]);
                    return;
                }
            }

            // [v1.9.5] Lógica de Rotação Sequencial de Clube de Vantagens
            const isClubeSlide = this.slides[this.currentSlide] === 'clube';
            if (isClubeSlide && this.clubeData.length > 0) {
                this.clubeIndex++;
                if (this.clubeIndex < this.clubeData.length) {
                    this.renderClubeItem(this.clubeData[this.clubeIndex]);
                    return;
                }
            }

            this.nextSlide();
        }, 10000); // [AJUSTE] 10 segundos por item
    },

    nextSlide() {
        if (this.isInterrupted) return;
        this.promoIndex = -1;
        this.clubeIndex = -1; // [v1.9.5] Reset Clube
        this.currentSlide = (this.currentSlide + 1) % this.slides.length;
        this.showSlide(this.currentSlide);
    },

    showSlide(idx) {
        document.querySelectorAll('.smart-slide').forEach(s => s.classList.remove('active'));
        const slideName = this.slides[idx];
        const el = document.getElementById('slide-' + slideName);
        if (el) el.classList.add('active');

        // Carga sob demanda
        if (slideName === 'promos') this.loadPromos();
        if (slideName === 'clube') this.loadClube();
        if (slideName === 'fidelidade') this.loadFidelidade();
    },

    async startSync() {
        const runSync = async () => {
            try {
                const res = await fetch('api/estado.php?servico_id=0');
                const json = await res.json();

                if (json.success) {
                    const d = json.data;
                    this.renderAgenda(d.agendados || []);
                    this.renderFila(d.fila || []);
                    this.renderHistory(d.historico || []); // [v1.8.0] Renderiza histórico
                    this.checkNewCall(d.chamando);
                }
            } catch (e) { console.error("Sync Error", e); }
        };

        runSync();
        setInterval(runSync, 3000);
    },

    async loadPromos() {
        try {
            const res = await fetch('api/promocoes.php');
            const json = await res.json();
            if (json.success && json.data && json.data.length > 0) {
                this.promoData = json.data;
                if (this.promoIndex === -1) {
                    this.promoIndex = 0;
                    this.renderPromoItem(this.promoData[0]);
                }
            } else if (this.slides[this.currentSlide] === 'promos') {
                this.nextSlide();
            }
        } catch (e) { console.error("Promo Error", e); }
    },

    renderPromoItem(p) {
        const container = document.getElementById('container-promos');
        if (!container) return;

        let imgHtml = '';
        if (p.imagem) {
            const imgUrl = p.imagem.startsWith('http') ? p.imagem : 'uploads/promocoes/' + p.imagem.replace('uploads/promocoes/', '').replace('../', '');
            imgHtml = `
                <img 
                    src="${imgUrl}" 
                    loading="lazy"
                    decoding="async"
                    alt="Promoção: ${p.titulo}"
                    style="max-height: 300px; width: auto; max-width: 90%; object-fit: contain; border-radius: 20px; margin-bottom: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); will-change: transform;"
                    class="promo-image-smart"
                    onerror="this.style.display='none'"
                >
            `;
        }

        container.innerHTML = `
            <div class="animate__animated animate__zoomIn">
                ${imgHtml}
                <h2 class="promo-title-smart">${p.titulo}</h2>
                <p class="promo-desc-smart">${p.descricao || ''}</p>
                <div class="promo-price-smart">${p.preco || ''}</div>
            </div>
        `;
    },

    async loadClube() {
        try {
            const res = await fetch('api/v1/clube_gestao.php?action=listar_planos');
            const json = await res.json();
            if (json.success && json.data.length > 0) {
                this.clubeData = json.data;
                // Se for o início do slide de clube, mostra o primeiro plano
                if (this.clubeIndex === -1) {
                    this.clubeIndex = 0;
                    this.renderClubeItem(this.clubeData[0]);
                }
            } else if (this.slides[this.currentSlide] === 'clube') {
                this.nextSlide();
            }
        } catch (e) { console.error("Clube Error", e); }
    },

    renderClubeItem(p) {
        const container = document.getElementById('container-clube');
        if (!container) return;

        // [v2.7.0] Fim do VW/VH: Usando PX para respeitar o ZOOM do navegador
        container.innerHTML = `
            <div class="animate__animated animate__zoomIn" style="background: var(--card-smart); padding: 40px; border-radius: 40px; border: 4px solid var(--warning); box-shadow: 0 0 40px rgba(245, 166, 35, 0.3); max-width: 800px; margin: 0 auto; overflow: hidden;">
                <div style="font-size: 80px; margin-bottom: 20px;">👑</div>
                <h2 style="font-size: 50px; color: #fff; margin: 0; font-weight: 900; text-transform: uppercase; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${p.nome}</h2>
                <p style="font-size: 30px; color: var(--secondary-smart); margin: 20px 0; font-weight: bold;">${p.qtd_cortes} cortes inclusos por mês</p>
                <div style="font-size: 80px; color: var(--accent-smart); font-weight: 900; line-height: 1;">R$ ${parseFloat(p.preco).toFixed(2)}</div>
                <p style="margin-top: 30px; font-size: 20px; opacity: 0.8; font-weight: 600;">Assine pelo nosso App!</p>
            </div>
        `;
    },

    async loadFidelidade() {
        try {
            const res = await fetch('api/v1/fidelidade_config.php');
            const json = await res.json();
            if (json.success && json.data && json.data.ativo == 1) {
                this.renderFidelidade(json.data);
            } else if (this.slides[this.currentSlide] === 'fidelidade') {
                this.nextSlide();
            }
        } catch (e) { console.error("Fidelidade Error", e); }
    },

    renderFidelidade(f) {
        const container = document.getElementById('container-fidelidade');
        if (!container) return;

        container.innerHTML = `
            <div class="animate__animated animate__zoomIn" style="background: var(--card-smart); padding: 60px; border-radius: 50px; border: 3px dashed var(--secondary-smart);">
                <div style="font-size: 120px; margin-bottom: 30px;">🎁</div>
                <h2 style="font-size: 60px; margin: 0;">GANHE PRÊMIOS EXCLUSIVOS</h2>
                <p style="font-size: 32px; margin: 30px 0; opacity: 0.9;">
                    A cada atendimento você ganha 1 ponto.<br>
                    Completando <b>${f.meta_pontos} pontos</b>, você ganha:
                </p>
                <div style="font-size: 70px; color: var(--accent-smart); font-weight: 900; text-transform: uppercase;">${f.premio_desc}</div>
            </div>
        `;
    },

    renderAgenda(agendados) {
        const container = document.getElementById('container-agenda');
        if (!container) return;

        // [v1.6.2 Fix] Inclui PRESENTE (Check-in feito) na agenda
        const proximos = agendados
            .filter(a => a.status === 'AGENDADO' || a.status === 'PRESENTE')
            .slice(0, 4);

        if (proximos.length === 0) {
            container.innerHTML = "<h3 style='font-size: 32px; opacity: 0.6;'>Nenhum agendamento para as próximas horas.</h3>";
            return;
        }

        // [v2.7.1] Estilo consistente com histórico e fila
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '20px';
        container.style.justifyContent = 'flex-start';
        container.style.alignItems = 'stretch';
        container.style.width = '100%';

        container.innerHTML = proximos.map(a => `
            <div class="history-item-smart animate__animated animate__fadeInDown">
                <span class="history-ticket" style="font-size: 36px;">${a.data_agendamento.split(' ')[1].substring(0, 5)}</span>
                <div class="history-name">${a.nome_cliente.toUpperCase()}</div>
                ${a.barbeiro_nome ? `<div class="history-guiche" style="background: var(--primary-smart);">${a.barbeiro_nome.split(' ')[0].toUpperCase()}</div>` : ''}
            </div>
        `).join('');
    },

    renderFila(fila) {
        const container = document.getElementById('container-fila');
        if (!container) return;

        const aguardando = fila.filter(f => f.status === 'AGUARDANDO' || f.status === 'PRESENTE').slice(0, 4);

        if (aguardando.length === 0) {
            container.innerHTML = "<h3 style='font-size: 32px; opacity: 0.6;'>A barbearia está livre no momento.</h3>";
            return;
        }

        // [v2.7.1] Estilo IDÊNTICO ao sidebar histórico para consistência visual
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '20px';
        container.style.justifyContent = 'flex-start';
        container.style.alignItems = 'stretch';
        container.style.width = '100%';

        container.innerHTML = aguardando.map(f => `
            <div class="history-item-smart animate__animated animate__fadeInDown" style="cursor: pointer; transition: all 0.3s ease;">
                <span class="history-ticket">${f.codigo}</span>
                <div class="history-name">${f.nome_cliente || 'CLIENTE LOCAL'}</div>
                <div class="history-guiche">${f.guiche || 'CADEIRA --'}</div>
            </div>
        `).join('');
    },

    checkNewCall(chamada) {
        if (!chamada) {
            if (this.isInterrupted) this.hideCall();
            return;
        }

        if (chamada.id !== this.lastCallId) {
            console.log("🔔 NOVA CHAMADA DETECTADA:", chamada.codigo);
            this.lastCallId = chamada.id;
            this.showCall(chamada);
        }
    },

    showCall(c) {
        this.isInterrupted = true;
        const overlay = document.getElementById('call-overlay');
        if (!overlay) return;

        document.getElementById('giant-ticket').innerText = c.codigo;
        document.getElementById('giant-name').innerText = c.nome_cliente || '';

        // [v1.6.2] Usa o nome real do guichê (ex: BANCADA 01)
        document.getElementById('giant-guiche').innerText = c.guiche.toUpperCase();

        overlay.style.display = 'flex';
        this.anunciarAlexa(c);

        setTimeout(() => this.hideCall(), 12000);
    },

    hideCall() {
        const overlay = document.getElementById('call-overlay');
        if (overlay) overlay.style.display = 'none';
        this.isInterrupted = false;
    },

    anunciarAlexa(c) {
        const header = document.querySelector('.tv-header-smart');
        const audioDing = new Audio('assets/audio/alert.mp3');

        // 1. Toca o Ding e Ativa Visual Alexa
        audioDing.play().catch(() => {
            console.warn("Ding audio não pôde ser reproduzido");
        });
        if (header) header.classList.add('alexa-active');

        setTimeout(async () => {
            const nome = c.nome_cliente ? c.nome_cliente : "Cliente da senha " + c.codigo;
            let local = c.guiche;
            let atendente = c.barbeiro;
            let texto = "";

            if (local.toUpperCase() === atendente.toUpperCase()) {
                texto = `${nome}, dirija-se ao ${local}.`;
            } else {
                texto = `${nome}, dirija-se ao ${local} para atendimento com ${atendente}.`;
            }

            let voiceSuccessful = false;

            // --- CANAL 1: PONTE NATIVA APK (ANDROID COM INTEGRAÇÃO) ---
            if (typeof AndroidVoz !== 'undefined' && !voiceSuccessful) {
                try {
                    console.log("🔊 CANAL 1: Usando Voz Nativa do APK...");
                    AndroidVoz.cancelar();
                    AndroidVoz.falar(texto);
                    voiceSuccessful = true;
                    setTimeout(() => { 
                        if (header && voiceSuccessful) header.classList.remove('alexa-active'); 
                    }, 5000);
                    return;
                } catch (e) { 
                    console.warn("Canal 1 falhou:", e);
                    voiceSuccessful = false;
                }
            }

            // --- CANAL 2: GOOGLE TRANSLATE TTS (MAIS CONFIÁVEL) ---
            if (!voiceSuccessful) {
                try {
                    console.log("🌐 CANAL 2: Tentando Google Translate TTS...");
                    const ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&q=${encodeURIComponent(texto)}&tl=pt-BR&client=tw-ob`;
                    const audioVoz = new Audio(ttsUrl);

                    audioVoz.onloadeddata = () => {
                        console.log("✅ Google TTS carregado com sucesso");
                    };

                    audioVoz.onended = () => {
                        voiceSuccessful = true;
                        if (header) header.classList.remove('alexa-active');
                    };

                    audioVoz.onerror = (e) => {
                        console.warn("Google TTS erro, tentando Web Speech API:", e);
                        voiceSuccessful = false;
                        tentarSpeechSynthesis();
                    };

                    audioVoz.oncanplay = () => {
                        audioVoz.play().catch((e) => {
                            console.warn("Erro ao reproduzir Google TTS:", e);
                            tentarSpeechSynthesis();
                        });
                    };

                } catch (e) {
                    console.warn("Erro ao tentar Google TTS:", e);
                    tentarSpeechSynthesis();
                }
            }

            // --- CANAL 3: WEB SPEECH API (ÚLTIMO RECURSO - PODE SER HORRÍVEL EM LG) ---
            const tentarSpeechSynthesis = () => {
                try {
                    if ('speechSynthesis' in window && !voiceSuccessful) {
                        console.log("🎙️ CANAL 3: Tentando Web Speech API (último recurso)...");
                        
                        // Cancela tudo antes
                        window.speechSynthesis.cancel();
                        
                        const msg = new SpeechSynthesisUtterance(texto);
                        msg.lang = 'pt-BR';
                        msg.rate = 0.85;  // Mais lento que o normal
                        msg.pitch = 1.0;
                        msg.volume = 1.0;
                        
                        msg.onend = () => {
                            voiceSuccessful = true;
                            if (header) header.classList.remove('alexa-active');
                        };
                        
                        msg.onerror = (e) => {
                            console.error("Web Speech API erro:", e);
                            voiceSuccessful = true;  // Marcar como tentado mesmo que falhe
                            if (header) header.classList.remove('alexa-active');
                        };
                        
                        window.speechSynthesis.speak(msg);
                    } else if (!('speechSynthesis' in window)) {
                        console.warn("Web Speech API não disponível neste navegador");
                        voiceSuccessful = true;
                        if (header) header.classList.remove('alexa-active');
                    }
                } catch (e) {
                    console.error("Erro crítico em Web Speech API:", e);
                    voiceSuccessful = true;
                    if (header) header.classList.remove('alexa-active');
                }
            };

        }, 800);
    },

    renderHistory(historico) {
        const container = document.getElementById('container-history');
        if (!container) return;

        // [v1.8.4] Só atualiza o HTML se houver mudança real nos dados
        const currentHash = JSON.stringify(historico);
        if (currentHash === this.lastHistoryHash) return;
        this.lastHistoryHash = currentHash;

        if (historico.length === 0) {
            container.innerHTML = "<p style='text-align:center; opacity:0.4; font-size:12px;'>Nenhum registro.</p>";
            return;
        }

        // Mostra as últimas 5 chamadas
        container.innerHTML = historico.slice(0, 5).map(h => `
            <div class="history-item-smart animate__animated animate__fadeInRight">
                <span class="history-ticket">${h.senha}</span>
                <span class="history-name">${h.nome_cliente ? h.nome_cliente.split(' ')[0] : 'CLIENTE'}</span>
                <span class="history-guiche">${h.guiche_nome || 'Local'}</span>
            </div>
        `).join('');
    }
};

document.addEventListener('DOMContentLoaded', () => SMART.init());
