/**
 * BT QUEUE LOYALTY ENGINE v1.0
 * Gerencia a identidade permanente do cliente e sistema de pontos.
 */

window.Loyalty = {
    api: '../api/v1/cliente.php',
    client: null,

    init() {
        const uuid = localStorage.getItem('bt_loyalty_uuid');
        if (uuid) {
            this.fetchProfile(uuid);
        } else {
            this.showView('register');
        }
    },

    async fetchProfile(uuid) {
        try {
            const res = await fetch(`${this.api}?uuid=${uuid}`);
            const json = await res.json();

            if (json.success) {
                this.client = json.cliente;
                this.renderProfile(json);
                this.showView('profile');
            } else {
                localStorage.removeItem('bt_loyalty_uuid');
                this.showView('register');
            }
        } catch (e) {
            console.error("Erro ao buscar perfil", e);
            this.showView('register');
        }
    },

    renderProfile(data) {
        const c = data.cliente;
        const fid = data.fidelidade;
        const config = fid.config;

        document.getElementById('welcome-title').innerText = "Olá, " + c.nome.split(' ')[0] + "!";
        document.getElementById('welcome-subtitle').innerText = "Bem-vindo de volta";
        document.getElementById('user-points').innerText = fid.saldo || 0;

        // v3.5.0: Agora dinâmico vindo do banco
        const meta = config.meta_pontos || 10;
        const premio = config.premio_desc || "Corte de Cabelo Grátis";

        document.getElementById('reward-name').innerText = premio;

        const faltam = meta - (fid.saldo % meta);
        if (fid.saldo > 0 && fid.saldo % meta === 0) {
            document.getElementById('reward-rules').innerText = `PARABÉNS! Você completou ${meta} pontos e ganhou seu prêmio!`;
            document.getElementById('reward-rules').style.color = "var(--success)";
        } else {
            document.getElementById('reward-rules').innerText = `Faltam ${faltam} atendimentos para o prêmio.`;
            document.getElementById('reward-rules').style.color = "";
        }
    },

    async registrar() {
        const nome = document.getElementById('reg-nome').value.trim();
        const whatsapp = document.getElementById('reg-whatsapp').value.trim();
        const nascimento = document.getElementById('reg-nascimento').value;

        if (!nome || !whatsapp) return alert("Por favor, preencha Nome e WhatsApp.");

        const btn = document.getElementById('btn-save');
        btn.disabled = true; btn.innerText = "CRIANDO PERFIL...";

        try {
            const res = await fetch(this.api, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nome, whatsapp, data_nascimento: nascimento
                })
            });
            const json = await res.json();

            if (json.success) {
                localStorage.setItem('bt_loyalty_uuid', json.cliente.uuid);
                // v3.5.0: Sincroniza com o UUID do agendamento legado
                localStorage.setItem('bt_cliente_uuid', json.cliente.uuid);
                this.fetchProfile(json.cliente.uuid);
            } else {
                alert(json.message);
                btn.disabled = false; btn.innerText = "CRIAR MEU PERFIL DIGITAL";
            }
        } catch (e) {
            alert("Falha ao conectar com o servidor.");
            btn.disabled = false;
        }
    },

    async recover() {
        const whatsapp = document.getElementById('rec-whatsapp').value.trim();
        if (!whatsapp) return alert("Informe seu WhatsApp.");

        const btn = document.getElementById('btn-recover');
        btn.disabled = true; btn.innerText = "BUSCANDO...";

        try {
            const res = await fetch(`${this.api}?action=buscar_whatsapp`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ whatsapp })
            });
            const json = await res.json();

            if (json.success) {
                // v3.5.0: Recuperação Simplificada (Preparado para OTP no futuro)
                localStorage.setItem('bt_loyalty_uuid', json.cliente.uuid);
                localStorage.setItem('bt_cliente_uuid', json.cliente.uuid);
                this.fetchProfile(json.cliente.uuid);
            } else {
                alert(json.message || "Cadastro não encontrado.");
                btn.disabled = false; btn.innerText = "BUSCAR MEU PERFIL";
            }
        } catch (e) {
            alert("Erro de conexão.");
            btn.disabled = false;
        }
    },

    goService() {
        // Redireciona para o Totem Mobile (v7.5)
        window.location.href = '../live_premium/index.php';
    },

    logout() {
        if (confirm("Deseja sair da sua conta neste celular?")) {
            localStorage.removeItem('bt_loyalty_uuid');
            localStorage.removeItem('bt_cliente_uuid');
            location.reload();
        }
    },

    showView(view) {
        document.getElementById('loading-view').classList.add('hidden');
        document.getElementById('register-view').classList.add('hidden');
        document.getElementById('profile-view').classList.add('hidden');
        document.getElementById('recovery-view').classList.add('hidden');

        document.getElementById(`${view}-view`).classList.remove('hidden');
    },

    showRecovery() { this.showView('recovery'); },
    showRegister() { this.showView('register'); }
};

document.addEventListener('DOMContentLoaded', () => Loyalty.init());
