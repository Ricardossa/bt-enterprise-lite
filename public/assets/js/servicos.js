/*
==========================================================
BT Queue Enterprise
Módulo: Serviços (Motor de UI e Integração REST)
Caminho: /var/www/html/painel_v4/public/assets/js/servicos.js
==========================================================
*/

window.BT = window.BT || {};

BT.servicos = {
    apiEndpoint: 'api/servicos.php',

    init() {
        this.carregar();
        this.registrarEventos();
    },

    registrarEventos() {
        const btnNovo = document.getElementById('btnNovo');
        if (btnNovo) {
            btnNovo.removeEventListener('click', this.novo);
            btnNovo.addEventListener('click', () => this.novo());
        }
    },

    async carregar() {
        const tbody = document.getElementById('listaServicos');
        if (!tbody) return;

        try {
            const resposta = await fetch(this.apiEndpoint, { method: 'GET' });
            const resultado = await resposta.json();

            if (resultado && resultado.success && Array.isArray(resultado.data)) {
                this.renderizar(resultado.data);
            } else {
                if (window.BT.toast) BT.toast.erro(resultado.message || 'Erro ao obter serviços.');
            }
        } catch (erro) {
            console.error('Erro na requisição GET:', erro);
        }
    },

    renderizar(dados) {
        const tbody = document.getElementById('listaServicos');
        if (!tbody) return;

        if (dados.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        Nenhum serviço cadastrado no momento.
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = dados.map(servico => `
            <tr id="linha-servico-${servico.id}">
                <td>
                    <span class="mr-2" style="color: ${servico.cor || '#1565C0'}">${servico.icone || '📋'}</span>
                    <strong>${servico.nome}</strong>
                </td>
                <td style="color:var(--success); font-weight:900;">R$ ${parseFloat(servico.preco).toFixed(2)}</td>
                <td><span class="badge bg-secondary">${servico.prefixo}</span></td>
                <td>${parseInt(servico.tempo_medio || 10)} min</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-info mr-1" onclick="BT.servicos.editar(${servico.id})">✏️ Configurar</button>
                    <button class="btn btn-sm btn-danger" onclick="BT.servicos.excluir(${servico.id})">🗑️ Remover</button>
                </td>
            </tr>
        `).join('');
    },

    novo() {
        const formularioHtml = this.construirFormularioHtml();
        if (window.BT.modal) {
            BT.modal.abrir(
                '👨‍💼 Cadastrar Novo Serviço',
                formularioHtml,
                () => this.salvar('POST')
            );
        }
    },

    async editar(id) {
        try {
            const resposta = await fetch(`${this.apiEndpoint}?id=${id}`, { method: 'GET' });
            const resultado = await resposta.json();

            if (resultado && resultado.success && resultado.data) {
                const servico = resultado.data;
                const formularioHtml = this.construirFormularioHtml(servico);

                if (window.BT.modal) {
                    BT.modal.abrir(
                        '✏️ Editar Serviço Existente',
                        formularioHtml,
                        () => this.salvar('PUT')
                    );
                }
            }
        } catch (erro) {
            console.error('Erro na recuperação do registro:', erro);
        }
    },

    async salvar(metodo) {
        const form = document.getElementById('formServicoModal');
        if (form && !form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const idElement = document.getElementById('modal_id');
        const dados = {
            codigo: document.getElementById('modal_codigo').value.trim(),
            nome: document.getElementById('modal_nome').value.trim(),
            slug: document.getElementById('modal_nome').value.toLowerCase().replace(/ /g, '-'), // Auto-slug
            prefixo: document.getElementById('modal_prefixo').value.trim().toUpperCase(),
            icone: document.getElementById('modal_icone').value.trim(),
            cor: document.getElementById('modal_cor').value,
            ordem: parseInt(document.getElementById('modal_ordem').value) || 0,
            tempo_medio: parseInt(document.getElementById('modal_tempo_medio').value) || 10,
            preco: parseFloat(document.getElementById('modal_preco').value) || 0
        };

        if (idElement && idElement.value) {
            dados.id = parseInt(idElement.value);
        }

        try {
            const resposta = await fetch(this.apiEndpoint, {
                method: metodo,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
            const resultado = await resposta.json();

            if (resultado && resultado.success) {
                if (window.BT.modal) BT.modal.fechar();
                if (window.BT.toast) BT.toast.sucesso('Operação realizada com sucesso!');
                this.carregar();
            }
        } catch (erro) {
            console.error(`Erro na requisição ${metodo}:`, erro);
        }
    },

    async excluir(id) {
        if (!confirm('Tem certeza absoluta que deseja remover este serviço?')) {
            return;
        }

        try {
            const resposta = await fetch(this.apiEndpoint, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(id) })
            });
            const resultado = await resposta.json();

            if (resultado && resultado.success) {
                if (window.BT.toast) BT.toast.sucesso('Serviço removido com sucesso.');
                this.carregar();
            }
        } catch (erro) {
            console.error('Erro na requisição DELETE:', erro);
        }
    },

    construirFormularioHtml(dados = {}) {
        return `
            <form id="formServicoModal" autocomplete="off" onsubmit="return false;">
                <input type="hidden" id="modal_id" value="${dados.id || ''}">
                
                <div class="form-group mb-3">
                    <label class="form-label font-weight-bold">Descrição do Serviço</label>
                    <input type="text" id="modal_nome" class="form-control" value="${dados.nome || ''}" placeholder="Ex: Corte Degradê" required style="font-size:18px; border:2px solid var(--secondary);">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group mb-3">
                        <label class="form-label font-weight-bold">Valor (R$)</label>
                        <input type="number" id="modal_preco" class="form-control" value="${dados.preco || 0}" step="0.01" min="0" required style="font-weight:900; color:var(--success);">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label font-weight-bold">Duração (min)</label>
                        <input type="number" id="modal_tempo_medio" class="form-control" value="${dados.tempo_medio || 30}" min="1" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group mb-3">
                        <label class="form-label font-weight-bold">Emoji / Ícone</label>
                        <input type="text" id="modal_icone" class="form-control" value="${dados.icone || '✂️'}" placeholder="Ex: 💈">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Cor de Destaque</label>
                        <input type="color" id="modal_cor" class="form-control form-control-color w-100" value="${dados.cor || '#1DB4FF'}">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group mb-3">
                        <label class="form-label font-weight-bold">Prefixo</label>
                        <input type="text" id="modal_prefixo" class="form-control" value="${dados.prefixo || 'S'}" required maxlength="3">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Cód. Interno</label>
                        <input type="text" id="modal_codigo" class="form-control" value="${dados.codigo || Date.now()}">
                    </div>
                </div>

                <input type="hidden" id="modal_slug" value="${dados.slug || 'servico'}">
                <input type="hidden" id="modal_ordem" value="${dados.ordem || 0}">
            </form>
        `;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    BT.servicos.init();
});
