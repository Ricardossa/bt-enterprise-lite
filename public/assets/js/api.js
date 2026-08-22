/**
 * BT QUEUE - API ENGINE v4.7.4 (Restored & Shielded)
 */

window.BT = window.BT || {};

BT.api = {
    // Detecta a base da API automaticamente
    base: window.location.pathname.includes('/live_premium/')
          ? '../api/'
          : 'api/',

    // Motor de Requisição Seguro
    async post(url, dados = {}) {
        const resposta = await fetch(this.base + url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(dados)
        });
        return await resposta.json();
    },

    async estado(servico_id, guiche_id) {
        const resposta = await fetch(
            this.base + "estado.php?servico_id=" + servico_id + "&guiche_id=" + guiche_id
        );
        return await resposta.json();
    },

    async chamar(servico_id, guiche_id) {
        return await this.post("chamar.php", { servico_id, guiche_id });
    },

    async chamarAgendado(id, guiche_id) {
        return await this.post("chamar_agendado.php", { id, guiche_id });
    },

    async rechamar(id) {
        return await this.post("rechamar.php", { id });
    },

    async finalizar(id) {
        return await this.post("finalizar.php", { id });
    },

    async emitir(prefixo, servico_id, cliente_uuid) {
        return await this.post("emitir.php", { prefixo, servico_id, cliente_uuid });
    },

    // Resolve URLs de ativos (Imagens/CSS/JS) independente de onde a página está
    url(path) {
        if (path.startsWith('http')) return path;

        const loc = window.location;
        let root = loc.protocol + '//' + loc.host + loc.pathname.substring(0, loc.pathname.lastIndexOf('/') + 1);

        if (root.endsWith('/live_premium/')) root = root.replace('/live_premium/', '/');
        if (root.endsWith('/api/')) root = root.replace('/api/', '/');

        return root + path.replace(/^\//, '');
    }
};

BT.toast = {
    sucesso(msg) { alert("✅ " + msg); },
    erro(msg) { alert("❌ " + msg); }
};
