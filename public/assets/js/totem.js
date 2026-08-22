/*
==========================================================
BT Queue Enterprise - Totem Kiosk Mode (Hybrid Edition)
==========================================================
*/

window.BT = window.BT || {};

BT.totem = {
    apiServicos: 'api/v1/agenda.php?action=servicos_por_barbeiro',
    apiBarbeiros: 'api/v1/agenda.php?action=listar_barbeiros',
    apiSenhas: 'api/senhas.php',
    apiConfig: 'api/configuracoes.php',

    idleTimeout: 8000, // Volta para o QR Code em 8 segundos
    idleTimer: null,
    currentToken: '',

    selectedBarberId: null,
    selectedServices: [],
    totalAmount: 0,

    init() {
        this.carregarBarbeiros();
        this.resetIdleTimer();
        this.iniciarCicloToken();

        document.addEventListener('click', () => this.resetIdleTimer());
        document.addEventListener('touchstart', () => this.resetIdleTimer());
    },

    async carregarBarbeiros() {
        const container = document.getElementById('containerBarbeiros');
        try {
            console.log("📡 Buscando equipe de profissionais...");
            const res = await fetch(this.apiBarbeiros);
            const json = await res.json();

            if (json.success && json.data.length > 0) {
                container.innerHTML = json.data.map(b => {
                    // [FIX] Caminho da Foto + Fallback Emoji
                    const fotoUrl = b.foto_url ? (b.foto_url.startsWith('http') ? b.foto_url : 'uploads/' + b.foto_url) : null;
                    const fotoHtml = fotoUrl ? `<img src="${fotoUrl}" style="width:140px; height:140px; border-radius:50%; object-fit:cover; border: 4px solid var(--secondary);" onerror="this.outerHTML='<span style=\'font-size:80px;\'>🧔</span>'">` : `<span style="font-size:80px;">🧔</span>`;

                    return `
                    <button class="totem-btn" style="border-color: var(--secondary); width:100%; height:280px; padding: 20px;" onclick="BT.totem.selectBarber(${b.id}, '${b.nome}')">
                        ${fotoHtml}
                        <label style="cursor:pointer; font-size:26px; margin-top:10px;">${b.nome}</label>
                    </button>
                `}).join('');
            } else {
                container.innerHTML = '<div style="padding:40px; color:var(--warning);"><h3>⚠️ NENHUM PROFISSIONAL ATIVO</h3><p>Cadastre os barbeiros no Painel Administrativo.</p></div>';
            }
        } catch (e) {
            console.error("Erro na carga do Totem:", e);
            container.innerHTML = '<div style="padding:40px; color:var(--danger);"><h3>❌ ERRO DE CONEXÃO</h3><p>Não foi possível falar com o servidor de dados.</p></div>';
        }
    },

    async selectBarber(id, nome) {
        this.selectedBarberId = id;
        this.selectedServices = [];
        this.totalAmount = 0;

        document.getElementById('barbeiroSelecionadoNome').innerText = "MENU DE " + nome.toUpperCase();
        document.getElementById('step-barber').classList.add('hidden');
        document.getElementById('step-services').classList.remove('hidden');
        document.getElementById('totemTotalValue').innerText = "R$ 0,00";
        document.getElementById('btnConfirmarServicos').disabled = true;

        const container = document.getElementById('containerServicos');
        container.innerHTML = '<p style="color:var(--text2);">Carregando menu...</p>';

        try {
            const res = await fetch(`${this.apiServicos}&operador_id=${id}`);
            const json = await res.json();
            if (json.success) {
                container.innerHTML = json.data.map(s => `
                    <button class="totem-btn service-item" id="btn-service-${s.id}" style="border-color: ${s.cor || 'var(--border)'}; width:100%; height:180px;" onclick="BT.totem.toggleService(${s.id}, ${s.preco})">
                        <span>${s.icone || '📋'}</span>
                        <label style="cursor:pointer; font-size:20px;">${s.nome}</label>
                        <b style="color:var(--success); font-size:18px;">R$ ${parseFloat(s.preco).toFixed(2)}</b>
                    </button>
                `).join('');
            }
        } catch (e) { container.innerHTML = 'Erro ao carregar menu.'; }
    },

    toggleService(id, preco) {
        const btn = document.getElementById(`btn-service-${id}`);
        const idx = this.selectedServices.indexOf(id);

        if (idx > -1) {
            this.selectedServices.splice(idx, 1);
            this.totalAmount -= preco;
            btn.style.background = 'var(--card)';
            btn.style.borderColor = 'var(--border)';
        } else {
            this.selectedServices.push(id);
            this.totalAmount += preco;
            btn.style.background = 'rgba(24, 201, 100, 0.1)';
            btn.style.borderColor = 'var(--success)';
        }

        document.getElementById('totemTotalValue').innerText = "R$ " + this.totalAmount.toFixed(2);
        document.getElementById('btnConfirmarServicos').disabled = (this.selectedServices.length === 0);
    },

    async confirmarServicos() {
        const btn = document.getElementById('btnConfirmarServicos');
        btn.disabled = true; btn.innerText = "GERANDO SENHA...";

        try {
            const res = await fetch(this.apiSenhas, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    servico_id: this.selectedServices[0], // ID principal
                    operador_id: this.selectedBarberId,
                    valor_total: this.totalAmount,
                    servicos_adicionais: this.selectedServices,
                    tipo: 'NORMAL'
                })
            });
            const json = await res.json();
            if (json.success) {
                this.mostrarSenha(json.data);
                this.dispararImpressao(json.data);
                setTimeout(() => this.backToBarbers(), 8000);
            } else {
                alert(json.message);
                btn.disabled = false; btn.innerText = "EMITIR MINHA SENHA";
            }
        } catch (e) { alert('Falha ao emitir senha.'); btn.disabled = false; }
    },

    backToBarbers() {
        this.selectedBarberId = null;
        document.getElementById('step-services').classList.add('hidden');
        document.getElementById('step-barber').classList.remove('hidden');
        this.resetIdleTimer();
    },

    configurarModoHibrido() {
        // Obsoleto: Substituído pela injeção via HTML no init()
    },

    async iniciarCicloToken() {
        const fetchToken = async () => {
            try {
                const res = await fetch('api/totem_token.php');
                const json = await res.json();
                if (json.success) {
                    this.currentToken = json.token;
                    if (window.BT_TOTEM_CONFIG) {
                        this.gerarQRDigital(window.BT_TOTEM_CONFIG);
                    }
                }
            } catch (e) { console.error("Erro ao renovar token do Totem."); }
        };

        fetchToken();
        setInterval(fetchToken, 30000); // Renova a cada 30 segundos
    },

    gerarQRDigital(cfg) {
        // --- LÓGICA DE URL OFICIAL (Melhorada v7.1) ---
        let base = cfg.url_local;

        // Se estivermos acessando por um domínio (não IP), ou o modo for cloud, usa a pública
        const isDomain = !/^[0-9.]+$/.test(window.location.hostname);
        if (cfg.modo === "cloud" || isDomain || window.location.hostname === 'lite.brandaotech.com.br') {
            base = cfg.url_publica || window.location.origin;
        }

        // Fallback final
        if (!base) base = window.location.origin;

        // Garante que a barra final esteja correta e aponta para o mobile
        let pathMobile = "live_premium/index.php"; // Removido o ?new=1 para preservar múltiplas senhas
        if (this.currentToken) {
            pathMobile += "?t=" + this.currentToken;
        }

        // --- SMART PATH DETECTION (Fix para Cliente vs Nuvem) ---
        let finalPath = "/";

        // Se a URL contém /painel_v4/, estamos em ambiente de Desenvolvimento ou Nuvem
        if (window.location.pathname.includes('/painel_v4/')) {
            finalPath = "/painel_v4/public/";
        }

        const destino = base.replace(/\/$/, "") + finalPath + pathMobile;

        console.log("Gerando QR Dinâmico:", destino);

        const qrContainer = document.getElementById('qrGiant');
        if (qrContainer) {
            const qr = qrcode(0, 'H');
            qr.addData(destino);
            qr.make();
            qrContainer.innerHTML = qr.createImgTag(12, 0);
        }
    },

    resetIdleTimer() {
        if (this.idleTimer) clearTimeout(this.idleTimer);
        this.idleTimer = setTimeout(() => this.showIdle(), this.idleTimeout);
    },

    wakeUp() {
        const overlay = document.getElementById('idleOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
        }
        this.resetIdleTimer();
    },

    showIdle() {
        const overlay = document.getElementById('idleOverlay');
        const modal = document.getElementById('modalSenhaTotem');

        // Se houver um modal de senha aberto, não volta para o idle ainda
        if (modal && modal.style.display === 'flex') return;

        if (overlay) {
            overlay.classList.remove('hidden');
        }
    },

    mostrarSenha(dados) {
        const elSenha = document.getElementById('displaySenha');
        elSenha.innerText = dados.senha;
        document.getElementById('displayServico').innerText = dados.servico_nome || 'Atendimento';

        // --- SMART FONT SIZE (v7.0.8 Diamond) ---
        // Evita que senhas longas (ex: prioritárias com 'P') vazem do card
        const len = dados.senha.length;
        if (len >= 6) elSenha.style.fontSize = '100px';
        else if (len >= 5) elSenha.style.fontSize = '120px';
        else elSenha.style.fontSize = '160px';

        const modal = document.getElementById('modalSenhaTotem');
        modal.style.display = 'flex';

        // Auto-fechar em 4 segundos e voltar para o Protetor de Tela mais rápido
        if (this.timer) clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            this.fecharModal();
            this.showIdle(); // Volta para o QR Code gigante após imprimir
        }, 4000);
    },

    async dispararImpressao(dados) {
        const empresa = window.BT_TOTEM_CONFIG?.empresa || 'BT Queue';

        // --- LÓGICA DE PONTE INTELIGENTE (V5.3) ---
        let printEndpoint = 'api/imprimir.php';
        let localIp = window.BT_TOTEM_CONFIG?.print_ip;

        // Fallback Automático: Se não houver IP configurado, assume que a impressora está no servidor
        if (!localIp || localIp === '127.0.0.1' || localIp === 'localhost') {
            printEndpoint = `http://${window.location.hostname}:8001/`;
        } else {
            printEndpoint = `http://${localIp}:8001/`;
        }

        console.log("🖨️ Acionando Motor de Impressão:", printEndpoint);

        try {
            const response = await fetch(printEndpoint, {
                method: 'POST',
                mode: 'cors', // Ativa CORS para chamadas entre Nuvem e Local
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    senha: dados.senha,
                    servico: dados.servico_nome,
                    empresa: empresa,
                    printer: 'BT_TICKET'
                })
            });

            const result = await response.json();
            if (!result.success) console.error("Falha na impressão:", result.message);
        } catch (e) {
            console.error("Erro ao conectar com a impressora:", e);
        }
    },

    fecharModal() {
        document.getElementById('modalSenhaTotem').style.display = 'none';
        this.resetIdleTimer();
    },

    // --- MÓDULO DE CHECK-IN NATIVO (v6.2 Diamond) ---
    showCheckin() {
        document.getElementById('inputCheckin').value = '';
        document.getElementById('modalCheckin').style.display = 'flex';
    },

    hideCheckin() {
        document.getElementById('modalCheckin').style.display = 'none';
    },

    key(k) {
        const input = document.getElementById('inputCheckin');
        if (k === 'BACK') input.value = input.value.slice(0, -1);
        else if (k === 'CLEAR') input.value = '';
        else if (k === 'SPACE') input.value += ' ';
        else if (input.value.length < 20) input.value += k;
    },

    async doCheckin() {
        const query = document.getElementById('inputCheckin').value.trim();
        if (!query) return alert("Digite seu nome ou token.");

        const btn = document.querySelector('#modalCheckin .bt-primary');
        btn.disabled = true; btn.innerText = "VERIFICANDO...";

        try {
            const res = await fetch('api/v1/agenda.php?action=checkin', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query })
            });
            const json = await res.json();

            if (json.success) {
                this.hideCheckin();
                // Mostra a senha agendada no display de sucesso
                this.mostrarSenha({
                    senha: json.data.codigo,
                    servico_nome: "CHECK-IN: " + json.data.nome_cliente
                });
                // Tenta imprimir o comprovante de chegada
                this.dispararImpressao({
                    senha: json.data.codigo,
                    servico_nome: "AGENDADO: " + json.data.hora
                });
            } else {
                alert(json.message || "Agendamento não encontrado para hoje.");
            }
        } catch (e) { alert("Erro ao realizar check-in."); }

        btn.disabled = false; btn.innerText = "CONFIRMAR CHEGADA";
    }
};

document.getElementById('btnFecharModal').onclick = () => BT.totem.fecharModal();

document.addEventListener('DOMContentLoaded', () => BT.totem.init());
