/**
 * BT QUEUE LIVE PREMIUM - EMITTER ENGINE (V4.2 - UTF8 FIX)
 * Lógica para escolha de serviço e redirecionamento.
 */

window.BT = window.BT || {};

console.log("🚀 DIAMOND v7.5 - EMITTER ENGINE ACTIVATED");

BT.emitter = {
    currentToken: '',
    deviceUuid: '',
    selectedBarberId: null,
    selectedServices: [],
    totalAmount: 0,

    async init() {
        this.deviceUuid = localStorage.getItem('bt_device_uuid');
        if (!this.deviceUuid) {
            this.deviceUuid = typeof crypto.randomUUID === 'function' ? crypto.randomUUID() : 'dev-' + Date.now();
            localStorage.setItem('bt_device_uuid', this.deviceUuid);
        }

        const urlParams = new URLSearchParams(window.location.search);
        this.currentToken = urlParams.get('t') || '';

        await this.loadBarberList();
    },

    async loadBarberList() {
        const container = document.getElementById('lista-barbeiros');
        try {
            const res = await fetch('../api/v1/agenda.php?action=listar_barbeiros');
            const json = await res.json();
            if (json.success) {
                // [LITE v3.5.7] Injeta Opção FILA GERAL no início
                let html = `
                    <button class="btn-premium-service" style="border-color: var(--primary); margin-bottom:15px; height:100px; background: rgba(29, 180, 255, 0.05);" onclick="BT.emitter.selectBarber(0, 'FILA GERAL')">
                        <span style="font-size:30px;">👥</span>
                        <div style="font-weight:900; color: var(--primary);">FILA GERAL</div>
                    </button>
                `;

                html += json.data.map(b => {
                    const foto = b.foto_url ? `../uploads/${b.foto_url.replace('uploads/', '')}` : '';
                    const avatar = foto ? `<img src="${foto}" style="width:50px; height:50px; border-radius:50%; object-fit:cover; margin-bottom:5px;">` : `<span style="font-size:30px;">🧔</span>`;

                    return `
                    <button class="btn-premium-service" style="border-color: var(--secondary); margin-bottom:15px; height:auto; padding:15px 10px;" onclick="BT.emitter.selectBarber(${b.id}, '${b.nome}')">
                        ${avatar}
                        <div style="font-weight:900;">${b.nome}</div>
                    </button>
                    `;
                }).join('');

                container.innerHTML = html;
            }
        } catch (e) { container.innerHTML = 'Erro ao carregar profissionais.'; }
    },

    async selectBarber(id, nome) {
        this.selectedBarberId = id;
        this.selectedServices = [];
        this.totalAmount = 0;

        document.getElementById('barbeiroNomeTitulo').innerText = "MENU DE " + nome.toUpperCase();
        document.getElementById('step-barber').classList.add('hidden');
        document.getElementById('step-services').classList.remove('hidden');
        document.getElementById('mobileTotalValue').innerText = "R$ 0,00";
        document.getElementById('btnConfirmarMobile').disabled = true;

        const list = document.getElementById('lista-servicos');
        list.innerHTML = '<p style="text-align:center; padding:20px; color:var(--text2);">Carregando catálogo...</p>';

        try {
            const res = await fetch(`../api/v1/agenda.php?action=servicos_por_barbeiro&operador_id=${id}`);
            const json = await res.json();
            if (json.success) {
                list.innerHTML = json.data.map(s => `
                    <div class="item-list-mobile" id="m-serv-${s.id}" onclick="BT.emitter.toggleService(${s.id}, ${s.current_price})" style="background:var(--sidebar); border:1px solid var(--border); padding:15px; border-radius:15px; margin-bottom:10px; display:flex; align-items:center; gap:15px;">
                        <span style="font-size:24px;">${s.icone}</span>
                        <div style="flex:1;">
                            <b style="display:block; font-size:14px;">${s.nome}</b>
                            <span style="font-size:12px; color:var(--secondary); font-weight:800;">
                                ${s.is_promo_today ? `<small style="text-decoration:line-through; opacity:0.5; margin-right:5px;">R$ ${parseFloat(s.preco).toFixed(2)}</small>` : ''}
                                R$ ${parseFloat(s.current_price).toFixed(2)}
                            </span>
                        </div>
                        <i class="fa-solid fa-circle-check check-icon" style="color:var(--success); display:none;"></i>
                    </div>
                `).join('');
            }
        } catch (e) { list.innerHTML = 'Erro ao carregar menu.'; }
    },

    toggleService(id, preco) {
        const el = document.getElementById(`m-serv-${id}`);
        const idx = this.selectedServices.indexOf(id);

        if (idx > -1) {
            this.selectedServices.splice(idx, 1);
            this.totalAmount -= preco;
            el.style.borderColor = 'var(--border)';
            el.querySelector('.check-icon').style.display = 'none';
        } else {
            this.selectedServices.push(id);
            this.totalAmount += preco;
            el.style.borderColor = 'var(--success)';
            el.querySelector('.check-icon').style.display = 'block';
        }

        document.getElementById('mobileTotalValue').innerText = "R$ " + this.totalAmount.toFixed(2);
        document.getElementById('btnConfirmarMobile').disabled = (this.selectedServices.length === 0);
    },

    async confirmarServicos() {
        const btn = document.getElementById('btnConfirmarMobile');
        btn.disabled = true; btn.innerText = "GERANDO SENHA...";

        // [v3.7.8] Prioriza o UUID da Fidelidade para garantir acúmulo de pontos
        const identityUuid = localStorage.getItem('bt_loyalty_uuid') || this.deviceUuid;

        try {
            const res = await fetch('../api/senhas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    servico_id: this.selectedServices[0],
                    operador_id: this.selectedBarberId,
                    valor_total: this.totalAmount,
                    servicos_adicionais: this.selectedServices,
                    device_id: identityUuid, // <--- IDENTIDADE UNIFICADA
                    t: this.currentToken,
                    tipo: 'NORMAL'
                })
            });
            const json = await res.json();
            if (json.success) {
                  // [FIX SESSION]: Mantém a senha atual no pool sem duplicar registros
                  let tickets = JSON.parse(localStorage.getItem('bt_premium_tickets') || '[]');
                  tickets = tickets.filter(t => t.id !== json.data.id);
                  tickets.push(json.data);
                  localStorage.setItem('bt_premium_tickets', JSON.stringify(tickets));

                window.location.href = 'acompanhar.php?uuid=' + json.data.cliente_uuid;
            } else {
                alert(json.message);
                btn.disabled = false; btn.innerText = "EMITIR MINHA SENHA";
            }
        } catch (e) { alert("Falha ao emitir senha."); }
    },

    backToBarbers() {
        document.getElementById('step-services').classList.add('hidden');
        document.getElementById('step-barber').classList.remove('hidden');
    },

    showCheckin() {
        document.getElementById('step-barber').classList.add('hidden');
        document.getElementById('checkin-form').classList.remove('hidden');
    },

    hideCheckin() {
        document.getElementById('checkin-form').classList.add('hidden');
        document.getElementById('step-barber').classList.remove('hidden');
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
