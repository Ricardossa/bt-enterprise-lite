/**
 * BT CLIENT PRO - APP ENGINE v2.2
 * Aplicação Nativa do Cliente Final
 */

window.App = {
    baseUrl: window.location.origin + window.location.pathname.replace('app_cliente/index.html', '').replace('app_cliente/', '').replace('app_cliente', ''),
    clientUuid: localStorage.getItem('bt_loyalty_uuid') || '',

    async init() {
        if (!this.clientUuid) {
            // [v2.2.2] Avisa à fidelidade que deve voltar para o App após o cadastro
            this.log("Cliente não identificado. Redirecionando para login...");
            window.location.href = this.baseUrl + 'fidelidade/index.php?redirect=app';
            return;
        }

        // [v2.4.5] Sincroniza Identidade com o APK se estiver em Iframe
        if (window.self !== window.top) {
            window.parent.postMessage({ type: 'bt_login', uuid: this.clientUuid }, '*');
        }

        await this.loadProfile();
        this.loadBarbers();
        this.loadPromos();
    },

    async loadProfile() {
        try {
            const res = await fetch(`${this.baseUrl}api/v1/cliente.php?uuid=${this.clientUuid}`);
            const json = await res.json();
            if (json.success) {
                document.getElementById('client-name').innerText = "Olá, " + json.cliente.nome.split(' ')[0] + "!";

                // [v2.4.0] Controle de Visibilidade da Fidelidade
                const loyaltyCard = document.getElementById('loyalty-card');
                if (loyaltyCard) {
                    if (json.fidelidade && json.fidelidade.config && json.fidelidade.config.ativo == 1) {
                        loyaltyCard.style.display = 'block';
                        document.getElementById('user-points').innerText = json.fidelidade.saldo || 0;
                    } else {
                        loyaltyCard.style.display = 'none';
                    }
                }

                // [v2.3.5] Busca Assinatura Ativa
                this.loadSubscription(json.cliente.id);

                // [v2.3.1] Carrega a logo e nome da barbearia dinamicamente
                const logoImg = document.getElementById('unit-logo-header');
                if (json.unit && json.unit.logo && logoImg) {
                    logoImg.src = this.baseUrl + json.unit.logo;
                }

                if (json.unit && json.unit.nome) {
                    // Opcional: Atualizar título da página ou outros elementos com o nome da unidade
                    console.log("Branding Unidade: " + json.unit.nome);
                }

                // [v2.3.0] Busca agendamento do dia para o cliente
                this.loadActiveBooking(json.cliente.id);
            }
        } catch (e) { this.log("Erro ao carregar perfil"); }
    },

    async loadActiveBooking(clienteId) {
        try {
            // Reaproveita a API de agendados
            const res = await fetch(`${this.baseUrl}api/v1/agenda.php?action=get_agendados_cliente&cliente_id=${clienteId}`);
            const json = await res.json();
            if (json.success && json.data.length > 0) {
                const b = json.data[0];
                const el = document.getElementById('next-booking');
                if (el) {
                    el.classList.remove('hidden');
                    document.getElementById('booking-time').innerText = b.hora;
                    document.getElementById('booking-code').innerText = "PIN: " + b.codigo;
                }
            }
        } catch(e) {}
    },

    async loadSubscription(clienteId) {
        try {
            const res = await fetch(`${this.baseUrl}api/v1/clube_gestao.php?action=ver_assinatura_ativa&cliente_id=${clienteId}`);
            const json = await res.json();

            if (json.success && json.data) {
                const s = json.data;
                const loyaltyCard = document.getElementById('loyalty-card');

                // Injeta card VIP logo acima do cartão fidelidade (v2.4.2)
                const vipHtml = `
                    <div class="card-fidelidade animate__animated animate__pulse" style="background: linear-gradient(135deg, #F5A623 0%, #D48806 100%); margin-bottom: 15px; border: 2px solid #fff;">
                        <div style="font-size:10px; font-weight:900; margin-bottom:10px; text-transform:uppercase; letter-spacing:1px;">👑 MEMBRO VIP</div>
                        <div class="points-val" style="color:#fff;">${s.cortes_restantes}</div>
                        <div class="points-label" style="color:#fff; opacity:1;">Cortes Restantes este mês</div>
                        <div style="font-size:10px; margin-top:15px; opacity:0.9; font-weight:bold;">Plano: ${s.plano_nome} · Expira em: ${s.data_fim}</div>
                    </div>
                `;

                if (loyaltyCard) {
                    loyaltyCard.insertAdjacentHTML('beforebegin', vipHtml);
                }
            }
        } catch(e) {}
    },

    async loadBarbers() {
        const container = document.getElementById('list-barbers');
        try {
            const res = await fetch(`${this.baseUrl}api/v1/agenda.php?action=listar_barbeiros`);
            const json = await res.json();
            if (json.success) {
                container.innerHTML = json.data.map(b => {
                    const foto = b.foto_url ? `${this.baseUrl}uploads/${b.foto_url.replace('uploads/', '')}` : '';
                    const avatar = foto ? `<img src="${foto}" style="width:60px; height:60px; border-radius:50%; object-fit:cover;">` : `<i class="fa-solid fa-user-tie" style="font-size:40px;"></i>`;
                    return `
                        <div class="mini-card" onclick="App.selectBarber(${b.id})" style="background:var(--card); border:1px solid var(--border); padding:20px; border-radius:20px; text-align:center;">
                            ${avatar}
                            <b style="display:block; margin-top:10px; font-size:14px;">${b.nome}</b>
                        </div>
                    `;
                }).join('');
            }
        } catch (e) { }
    },

    async loadPromos() {
        const container = document.getElementById('list-promos');
        try {
            const res = await fetch(`${this.baseUrl}api/v1/cliente.php?uuid=${this.clientUuid}`);
            const json = await res.json();

            if (json.success) {
                let html = '';
                const config = json.fidelidade.config;

                // 1. Regra de Fidelidade (MOSTRA APENAS SE ATIVA)
                if (config.ativo == 1) {
                    html += `
                        <div class="card-fidelidade" style="background:var(--card); border: 2px dashed var(--secondary); padding:20px; box-shadow:none; margin-bottom:15px;">
                            <b style="color:var(--secondary); font-size:16px;">🎁 REGRA DE PONTUAÇÃO</b>
                            <p style="font-size:12px; color:var(--text2); margin-top:5px;">
                                A cada 1 atendimento, você ganha 1 ponto. Ao completar <b>${config.meta_pontos} pontos</b>, você ganha: <br><br> <span style="font-size:18px; color:#fff; font-weight:900;">${config.premio_desc.toUpperCase()}</span>
                            </p>
                        </div>
                    `;
                }

                // 2. Planos de Assinatura & Combos (v2.4.3 Dinâmico)
                try {
                    const resPlanos = await fetch(`${this.baseUrl}api/v1/clube_gestao.php?action=listar_planos`);
                    const jsonPlanos = await resPlanos.json();

                    if (jsonPlanos.success && jsonPlanos.data.length > 0) {
                        html += `<h3 style="font-size:11px; color:var(--text3); text-transform:uppercase; margin:20px 0 10px; letter-spacing:1px;">Planos & Ofertas</h3>`;

                        html += jsonPlanos.data.map(p => {
                            const isPromo = (p.nome === 'BEM-VINDO');
                            return `
                                <div class="btn-main" style="padding:15px; gap:15px; border-color:${isPromo ? 'var(--success)' : 'var(--warning)'};">
                                    <div style="width:60px; height:60px; background:rgba(${isPromo ? '24, 201, 100' : '245, 166, 35'}, 0.1); border-radius:15px; display:flex; align-items:center; justify-content:center;">
                                         <i class="fa-solid fa-${isPromo ? 'user-plus' : 'crown'}" style="font-size:24px; color:${isPromo ? 'var(--success)' : 'var(--warning)'};"></i>
                                    </div>
                                    <div>
                                        <b>${p.nome.toUpperCase()}</b>
                                        <span>${p.descricao || (p.preco > 0 ? `R$ ${parseFloat(p.preco).toFixed(2)}/mês · ${p.qtd_cortes} cortes` : 'Oferta Especial')}</span>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }
                } catch(e) {}

                container.innerHTML = html;
            }
        } catch (e) { }
    },

    go(screen) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById(screen).classList.add('active');

        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    },

    selectBarber(id) {
        window.location.href = `${this.baseUrl}agendar.php?operador_id=${id}&uuid=${this.clientUuid}`;
    },

    log(msg) { console.log("[BT-APP] " + msg); }
};

document.addEventListener('DOMContentLoaded', () => App.init());
