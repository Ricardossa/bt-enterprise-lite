/**
 * BT BARBER PRO - APP ENGINE v2.1
 * Desenvolvido por Brandão Tech (NOC Diamond Edition)
 */

window.App = {
    pollingInterval: null,
    guicheId: 1,
    services: [],
    currentTab: 'dashboard',
    periodoGanhos: 'hoje',

    async init() {
        console.log("?? App Initializing...");
        this.log("Iniciando motores...");
        this.loadPrivacy();

        // Carrega configurações
        const savedUrl = localStorage.getItem('bt_manual_url');
        const savedOpId = localStorage.getItem('bt_operador_id');

        if (!savedUrl || !savedOpId) {
            this.showConfig();
            return;
        }

        this.log("Sincronizando com " + savedUrl);

        try {
            const auth = await API.autoAuth();
            if (auth.success) {
                this.log("Autenticado: " + auth.operador.nome);
                document.getElementById('op-name').innerText = auth.operador.nome;
                document.getElementById('op-role').innerText = auth.operador.nivel;

                // [v2.2.0] Carrega Logo da Unidade
                const unitLogo = auth.unit_logo || (auth.tenant && auth.tenant.logo);
                if (unitLogo) {
                    const finalLogo = unitLogo.startsWith('http') ? unitLogo : savedUrl.replace(/\/$/, '') + '/' + unitLogo;
                    const loginLogo = document.getElementById('app-logo-login');
                    if (loginLogo) loginLogo.src = finalLogo;
                }

                // [v2.1.8] Sincroniza Identidade da Unidade (Tenant)
                if (auth.tenant && auth.tenant.uuid) {
                    localStorage.setItem('bt_tenant_uuid', auth.tenant.uuid);
                    API.TENANT_UUID = auth.tenant.uuid;
                    this.log("Unidade: " + auth.tenant.slug);
                }

                // [v2.1.5] Sincroniza a Cadeira (Guichê) correta do barbeiro
                if (auth.operador.guiche_id) {
                    this.guicheId = auth.operador.guiche_id;
                    this.log("Cadeira identificada: " + this.guicheId);
                }

                // Esconde tela de login e inicia dashboard
                document.getElementById('login-screen').classList.add('hidden');

                this.loadServices();
                this.startDashboard();
            } else {
                this.log("Erro na autenticação: " + auth.message);
                this.showConfig();
            }
        } catch (e) {
            this.log("Falha crítica de rede.");
            this.showConfig();
        }
    },

    async loadServices() {
        try {
            const res = await API.request('servicos.php');
            if (res.data) {
                this.services = res.data;
                const select = document.getElementById('balcony-service');
                if (select) {
                    select.innerHTML = this.services.map(s => `<option value="${s.id}">${s.nome}</option>`).join('');
                }
            }
        } catch(e) {}
    },

    togglePrivacy() {
        const card = document.getElementById('card-ganhos');
        const icon = document.getElementById('toggle-privacy');
        const isHidden = card.style.filter === 'blur(10px)';
        card.style.filter = isHidden ? '' : 'blur(10px)';
        card.style.opacity = isHidden ? '1' : '0.4';
        icon.className = isHidden ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        localStorage.setItem('mobile_privacy', isHidden ? '0' : '1');
    },

    loadPrivacy() {
        if (localStorage.getItem('mobile_privacy') === '1') {
            setTimeout(() => this.togglePrivacy(), 500);
        }
    },

    async setStatus(status) {
        try {
            const res = await API.request('v1/operador_status.php', 'POST', { status });
            if (res.success) {
                document.getElementById('pill-online').style.borderColor = (status === 'ONLINE') ? 'var(--success)' : 'var(--border)';
                document.getElementById('pill-break').style.borderColor = (status === 'BREAK') ? 'var(--warning)' : 'var(--border)';
            }
        } catch(e) {}
    },

    showBalcony() { document.getElementById('modal-balcony').classList.remove('hidden'); },
    hideBalcony() { document.getElementById('modal-balcony').classList.add('hidden'); },

    async emitirBalcao() {
        const nome = document.getElementById('balcony-name').value.trim();
        const sid = document.getElementById('balcony-service').value;
        if (!nome) return alert("Digite o nome");

        try {
            const res = await API.request('senhas.php', 'POST', {
                servico_id: sid,
                operador_id: localStorage.getItem('bt_operador_id'),
                nome_cliente: nome,
                tipo: 'NORMAL'
            });
            if (res.success) {
                this.hideBalcony();
                this.sync();
            }
        } catch(e) { alert("Erro ao lançar"); }
    },

    async marcarComoPago() {
        if (!API.currentUuid) return;
        if (!confirm("Confirmar recebimento?")) return;
        try {
            const res = await API.request('v1/agenda.php?action=confirm_pay', 'POST', { uuid: API.currentUuid });
            if (res.success) this.sync();
        } catch(e) {}
    },

    startDashboard() {
        this.sync();
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        this.pollingInterval = setInterval(() => this.sync(), 3000);
    },

    async sync() {
        try {
            const res = await API.getEstado(this.guicheId);
            if (res.success) {
                const d = res.data;

                // [v2.1.7] Normalização de números para qualquer localidade
                const parseGanhos = (val) => {
                    if (val === null || val === undefined) return 0;
                    if (typeof val === 'number') return val;
                    return parseFloat(String(val).replace(',', '.')) || 0;
                };

                let ganhos = parseGanhos(d.ganhos_hoje);

                document.getElementById('val-ganhos').innerText = "R$ " + ganhos.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                // Barra de Meta (Meta sugerida: R$ 500 para barbeiro, R$ 1500 para admin)
                const userRole = document.getElementById('op-role').innerText.toUpperCase();
                const metaValue = (userRole === 'ADMIN' || userRole === 'ADMINISTRADOR') ? 1500 : 500;
                const metaPerc = Math.min((ganhos / metaValue) * 100, 100);
                document.getElementById('bar-meta').style.width = metaPerc + "%";

                // Contadores
                document.getElementById('val-fila').innerText = d.fila.length;
                document.getElementById('val-agenda').innerText = d.agendados.length;

                // Atendimento Atual
                if (d.chamando) {
                    document.getElementById('box-busy').classList.remove('hidden');
                    document.getElementById('box-free').classList.add('hidden');
                    document.getElementById('cur-senha').innerText = d.chamando.codigo;
                    document.getElementById('cur-cliente').innerText = d.chamando.nome_cliente || 'Cliente de Porta';
                    document.getElementById('cur-servico').innerText = 'SERVIÇO: ' + (d.chamando.servico_nome || '---');

                    API.currentSenhaId = d.chamando.id;
                    API.currentUuid = d.chamando.uuid; // [FIX] Salva UUID para confirmação de pagamento

                    // Controle do botão de pagamento
                    const btnPay = document.getElementById('btn-pay');
                    if (btnPay) {
                        btnPay.style.display = (d.chamando.pagamento_status === 'PAGO') ? 'none' : 'flex';
                    }
                } else {
                    document.getElementById('box-busy').classList.add('hidden');
                    document.getElementById('box-free').classList.remove('hidden');
                    API.currentSenhaId = null;
                }

                // [v2.2.0] Sincroniza botões de status com o banco
                const currentStatus = d.status || 'ONLINE';

                const btnOnline = document.getElementById('pill-online');
                const btnBreak = document.getElementById('pill-break');

                if (currentStatus === 'ONLINE') {
                    btnOnline.style.borderColor = 'var(--success)';
                    btnOnline.style.background = 'rgba(24, 201, 100, 0.2)';
                    btnOnline.style.boxShadow = '0 0 15px rgba(24, 201, 100, 0.3)';

                    btnBreak.style.borderColor = 'var(--border)';
                    btnBreak.style.background = 'var(--card)';
                    btnBreak.style.boxShadow = 'none';
                } else {
                    btnOnline.style.borderColor = 'var(--border)';
                    btnOnline.style.background = 'var(--card)';
                    btnOnline.style.boxShadow = 'none';

                    btnBreak.style.borderColor = 'var(--warning)';
                    btnBreak.style.background = 'rgba(245, 166, 35, 0.2)';
                    btnBreak.style.boxShadow = '0 0 15px rgba(245, 166, 35, 0.3)';
                }

                // Listas do Dashboard
                this.renderList('list-agenda', d.agendados, 'agenda');
                this.renderList('list-fila', d.fila, 'fila');

                // [v2.2.5 FIX]: Se estiver na aba Dashboard, renderiza o histórico simples.
                // Se estiver na aba Caixa, NÃO FAZ NADA para não apagar os valores em R$ carregados pelo renderGanhosDetalhados().
                if (this.currentTab === 'dashboard') {
                    this.renderList('list-history', d.historico, 'history');
                } else if (this.currentTab === 'caixa') {
                    // Sincroniza apenas o valor do card de ganhos se necessário, mas não toca na lista detalhada
                }

                document.getElementById('conn-status').innerHTML = '<i class="fa-solid fa-circle-dot"></i> ONLINE';
                document.getElementById('conn-status').style.color = 'var(--success)';
            }
        } catch (e) {
            document.getElementById('conn-status').innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> OFFLINE';
            document.getElementById('conn-status').style.color = 'var(--danger)';
        }
    },

    renderList(containerId, items, type) {
        const el = document.getElementById(containerId);
        if (!el) return;

        if (!items || items.length === 0) {
            el.innerHTML = `<p style="padding:15px; color:var(--text3); font-size:12px; text-align:center;">Vazio no momento.</p>`;
            return;
        }

        el.innerHTML = items.map(i => {
            const time = i.data_agendamento ? i.data_agendamento.split(' ')[1].substring(0,5) : (i.chamada_em ? i.chamada_em.split(' ')[1].substring(0,5) : '--:--');
            const zap = i.whatsapp ? `<a href="https://wa.me/55${i.whatsapp.replace(/\D/g,'')}" class="wa"><i class="fa-brands fa-whatsapp"></i></a>` : '';

            // [v2.1.2] Fix para campos divergentes entre Lite e Full
            const codigoExibido = i.codigo || i.senha || '---';
            const nomeExibido = i.nome_cliente || i.cliente_nome || 'Cliente';

            return `
                <div class="list-item">
                    <div class="time">${time}</div>
                    <div class="info">
                        <b>${nomeExibido}</b>
                        <span>${codigoExibido} · ${i.servico_nome || 'Atendimento'}</span>
                    </div>
                    ${zap}
                </div>
            `;
        }).join('');
    },

    async chamarProximo() {
        try {
            const res = await API.chamar(this.guicheId);
            if (!res.success) alert(res.message);
            else this.sync();
        } catch (e) { alert("Erro ao chamar."); }
    },

    async finalizar() {
        if (!API.currentSenhaId) return;
        try {
            const res = await API.finalizar(API.currentSenhaId);
            if (res.success) this.sync();
        } catch (e) { alert("Erro ao finalizar."); }
    },

    async rechamar() {
        if (!API.currentSenhaId) return;
        try { await API.rechamar(API.currentSenhaId); } catch (e) {}
    },

    irPara(screen, btn) {
        this.currentTab = screen;

        // Alterna visibilidade das seções
        ['dashboard', 'caixa', 'config'].forEach(s => {
            const el = document.getElementById('screen-' + s);
            if (el) el.classList.add('hidden');
        });
        const target = document.getElementById('screen-' + screen);
        if (target) target.classList.remove('hidden');

        // Alterna estado dos botões
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        if (btn) btn.classList.add('active');

        // [v2.2.0] Força sincronismo imediato ao trocar de aba para evitar delay
        this.sync();
    },

    async renderGanhosDetalhados() {
        const container = document.getElementById('list-history');
        if (!container) return;

        let inicio, fim;
        const hoje = new Date();
        const format = (d) => {
            const z = d.getTimezoneOffset() * 60 * 1000;
            const local = new Date(d.getTime() - z);
            return local.toISOString().split('T')[0];
        };

        if (this.periodoGanhos === 'semana') {
            const agora = new Date();
            const dia = agora.getDay();
            const diff = agora.getDate() - dia + (dia === 0 ? -6 : 1);
            inicio = format(new Date(agora.setDate(diff)));
            fim = format(new Date());
        } else if (this.periodoGanhos === 'mes') {
            inicio = format(new Date(hoje.getFullYear(), hoje.getMonth(), 1));
            fim = format(new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0));
        } else {
            inicio = format(hoje);
            fim = format(hoje);
        }

        try {
            const res = await API.request(`v1/operador_ganhos.php?inicio=${inicio}&fim=${fim}`);
            if (res.success) {
                const d = res.data;
                let html = `
                    <div style="background:linear-gradient(135deg, #132238 0%, #0D1B2A 100%); padding:25px; border-radius:20px; margin-bottom:20px; border:1px solid var(--success); text-align:center;">
                        <span style="color:var(--text2); font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1px;">GANHO REAL NO PERÍODO</span>
                        <b style="color:var(--success); font-size:32px; display:block; margin-top:5px;">R$ ${d.ganho_real.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</b>
                        <div style="margin-top:10px;">
                            <span class="badge" style="background:var(--primary); font-size:10px; padding:4px 10px; border-radius:20px; color:#fff; font-weight:bold;">${d.total_servicos} ATENDIMENTOS</span>
                        </div>
                    </div>
                    <h3 style="font-size:11px; color:var(--text3); text-transform:uppercase; margin-bottom:12px; letter-spacing:1px;"><i class="fa-solid fa-receipt"></i> Detalhamento</h3>
                `;

                if (d.historico && d.historico.length > 0) {
                    html += d.historico.map(h => `
                        <div class="list-item">
                            <div class="time">${h.chamada_em ? h.chamada_em.split(' ')[1].substring(0,5) : '--:--'}</div>
                            <div class="info">
                                <b>${h.senha}</b>
                                <span>${h.servicos_desc || 'Atendimento'}</span>
                            </div>
                            <b style="color:var(--success); font-size:14px;">+ R$ ${h.ganho_liquido.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</b>
                        </div>
                    `).join('');
                } else {
                    html += `<p style="padding:40px; color:var(--text3); font-size:12px; text-align:center;">Nenhum ganho registrado neste período.</p>`;
                }

                container.innerHTML = html;
            }
        } catch (e) { console.error(e); container.innerHTML = "Erro ao carregar ganhos."; }
    },

    setPeriodoGanhos(p) {
        this.periodoGanhos = p;
        ['hoje', 'semana', 'mes'].forEach(btn => {
            const el = document.getElementById('btn-g-' + btn);
            if (el) {
                el.style.background = (btn === p) ? 'var(--primary)' : 'transparent';
                el.style.color = (btn === p) ? '#fff' : 'var(--text2)';
            }
        });
        this.renderGanhosDetalhados();
    },

    showConfig() {
        document.getElementById('input-url').value = localStorage.getItem('bt_manual_url') || '';
        document.getElementById('input-op-id').value = localStorage.getItem('bt_operador_id') || '';
        document.getElementById('input-op-pass').value = localStorage.getItem('bt_operador_pass') || '';
        document.getElementById('modal-config').classList.remove('hidden');
    },

    hideConfig() {
        document.getElementById('modal-config').classList.add('hidden');
    },

    saveConfig() {
        const url = document.getElementById('input-url').value.trim();
        const opId = document.getElementById('input-op-id').value;
        const opPass = document.getElementById('input-op-pass').value.trim();

        if (!url || !opId || !opPass) return alert("Preencha todos os campos, incluindo a senha.");

        localStorage.setItem('bt_manual_url', url);
        localStorage.setItem('bt_operador_id', opId);
        localStorage.setItem('bt_operador_pass', opPass);

        location.reload();
    },

    logout() {
        if (confirm("Deseja realmente desconectar?")) {
            localStorage.clear();
            location.reload();
        }
    },

    log(msg) {
        const console = document.getElementById('debug-console');
        if (console) {
            const time = new Date().toLocaleTimeString();
            console.innerHTML += `[${time}] ${msg}<br>`;
            console.scrollTop = console.scrollHeight;
        }
    }
};

document.addEventListener('DOMContentLoaded', () => App.init());
