const API = {
    BASE_URL: localStorage.getItem('bt_manual_url') || '',
    OP_ID: localStorage.getItem('bt_operador_id') || 0,
    TENANT_UUID: localStorage.getItem('bt_tenant_uuid') || '',
    currentSenhaId: null,

    async request(endpoint, method = 'GET', data = null) {
        if (!this.BASE_URL) return { success: false, message: 'URL não configurada' };

        // [v2.1.8] Atualiza UUID em tempo real
        this.TENANT_UUID = localStorage.getItem('bt_tenant_uuid') || '';

        const url = this.BASE_URL.replace(/\/$/, '') + '/api/' + endpoint;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-BT-BYPASS': 'DIAMOND-MOBILE-2026',
                'X-BT-TENANT-UUID': this.TENANT_UUID
            }
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        return await response.json();
    },

    autoAuth() {
        this.BASE_URL = localStorage.getItem('bt_manual_url') || '';
        this.OP_ID = localStorage.getItem('bt_operador_id') || 0;
        const opPass = localStorage.getItem('bt_operador_pass') || '';

        if (!this.BASE_URL) return Promise.resolve({ success: false, message: 'URL ausente' });

        const url = this.BASE_URL.replace(/\/$/, '') + '/mobile_auth.php?op_id=' + this.OP_ID + '&pass=' + encodeURIComponent(opPass);

        return fetch(url, {
            headers: {
                'X-BT-BYPASS': 'DIAMOND-MOBILE-2026',
                'X-BT-TENANT-UUID': this.TENANT_UUID
            }
        }).then(res => res.json());
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
