/**
 * BT BARBER PRO - API ENGINE v2.3 (Diamond Native Fixed)
 */
const API = {
    BASE_URL: '',
    OP_ID: 0,
    TENANT_UUID: '',
    currentSenhaId: null,

    init() {
        let url = localStorage.getItem('bt_manual_url') || '';
        if (url) {
            // Limpeza de URL: Remove caminhos extras como /app_pro/ e garante o domínio raiz
            try {
                const parsed = new URL(url.startsWith('http') ? url : 'https://' + url);
                this.BASE_URL = parsed.origin; // Pega apenas o protocolo + domínio
            } catch(e) {
                this.BASE_URL = url.replace(/\/$/, '');
            }
        }
        this.OP_ID = localStorage.getItem('bt_operador_id') || 0;
        this.TENANT_UUID = localStorage.getItem('bt_tenant_uuid') || '';
    },

    async request(endpoint, method = 'GET', data = null) {
        this.init();
        if (!this.BASE_URL) return { success: false, message: 'URL não configurada' };

        const url = this.BASE_URL.replace(/\/$/, '') + '/api/' + endpoint;

        console.log("📡 API REQUEST:", method, url);

        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-BT-BYPASS': 'DIAMOND-MOBILE-2026'
            }
        };

        if (this.TENANT_UUID) {
            options.headers['X-BT-TENANT-UUID'] = this.TENANT_UUID;
        }

        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            if (!response.ok) throw new Error("HTTP " + response.status);
            return await response.json();
        } catch(e) {
            console.error("❌ API ERROR:", e.message);
            return { success: false, message: e.message };
        }
    },

    async autoAuth() {
        this.init();
        if (!this.BASE_URL) return { success: false, message: 'URL ausente' };

        const url = this.BASE_URL.replace(/\/$/, '') + '/mobile_auth.php?op_id=' + this.OP_ID;

        console.log("🔐 AUTH REQUEST:", url);

        try {
            const res = await fetch(url, {
                headers: {
                    'X-BT-BYPASS': 'DIAMOND-MOBILE-2026',
                    'X-BT-TENANT-UUID': this.TENANT_UUID
                }
            });
            return await res.json();
        } catch(e) {
            return { success: false, message: 'Falha na conexão com o servidor' };
        }
    },

    getEstado(guicheId = 1) {
        return this.request(`estado.php?servico_id=0&guiche_id=${guicheId}`);
    },

    chamar(guicheId = 1) {
        return this.request('chamar.php', 'POST', { servico_id: 0, guiche_id: guicheId });
    },

    rechamar(senhaId) {
        return this.request('rechamar.php', 'POST', { id: senhaId });
    },

    finalizar(senhaId) {
        return this.request('finalizar.php', 'POST', { id: senhaId });
    }
};
