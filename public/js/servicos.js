const API_URL = '../api/servicos.php';

document.addEventListener('DOMContentLoaded', () => {
    listarServicos();
    
    const nomeInput = document.getElementById('servico-nome');
    if (nomeInput) {
        nomeInput.addEventListener('input', (e) => {
            window.currentSlug = e.target.value
                .toLowerCase()
                .normalize('NFD')
                .replace(/[̀-Ͽ]/g, '')
                .replace(/[^a-z0-9 ]/g, '')
                .replace(/\s+/g, '-');
        });
    }
});

function mostrarToast(mensagem, tipo = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    const bgColor = tipo === 'success' ? 'bg-green-500' : 'bg-red-500';
    toast.className = `${bgColor} text-white px-4 py-3 rounded-lg shadow-lg text-sm transition-all duration-300 opacity-0 translate-y-2`;
    toast.textContent = mensagem;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('opacity-0', 'translate-y-2');
    }, 10);
    
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function listarServicos() {
    fetch(API_URL)
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('tabela-servicos');
            if (!tbody) return;
            tbody.innerHTML = '';
            
            if (!data.success || !data.data || data.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Nenhum serviço cadastrado.</td></tr>`;
                return;
            }
            
            data.data.forEach(s => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50 transition-colors';
                tr.innerHTML = `
                    <td class="px-6 py-4 font-medium text-gray-900 flex items-center gap-3">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full text-lg" style="background-color: ${s.cor}40; text-shadow: 0px 0px 2px ${s.cor};">${s.icone}</span>
                        <span>${s.nome}</span>
                    </td>
                    <td class="px-6 py-4 font-mono text-xs">${s.codigo}</td>
                    <td class="px-6 py-4 font-bold">${s.prefixo}</td>
                    <td class="px-6 py-4">${s.tempo_medio} min</td>
                    <td class="px-6 py-4">${s.ordem}</td>
                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                        <button onclick='editarServico(${JSON.stringify(s)})' class="text-blue-600 hover:text-blue-900 font-medium">Editar</button>
                        <span class="text-gray-300">|</span>
                        <button onclick="excluirServico(${s.id})" class="text-red-600 hover:text-red-900 font-medium">Excluir</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(() => mostrarToast('Falha ao carregar serviços.', 'error'));
}

function abrirModal() {
    const form = document.getElementById('form-servico');
    if (form) form.reset();
    
    const idInput = document.getElementById('servico-id');
    if (idInput) idInput.value = '';
    
    window.currentSlug = '';
    
    const title = document.getElementById('modal-title');
    if (title) title.textContent = 'Novo Serviço';
    
    const modal = document.getElementById('modal-servico');
    if (modal) modal.classList.remove('hidden');
}

function fecharModal() {
    const modal = document.getElementById('modal-servico');
    if (modal) modal.classList.add('hidden');
}

window.abrirModal = abrirModal;
window.fecharModal = fecharModal;

function editarServico(s) {
    abrirModal();
    document.getElementById('modal-title').textContent = 'Editar Serviço';
    document.getElementById('servico-id').value = s.id;
    document.getElementById('servico-codigo').value = s.codigo;
    document.getElementById('servico-nome').value = s.nome;
    document.getElementById('servico-prefixo').value = s.prefixo;
    document.getElementById('servico-icone').value = s.icone;
    document.getElementById('servico-cor').value = s.cor;
    document.getElementById('servico-tempo').value = s.tempo_medio;
    document.getElementById('servico-ordem').value = s.ordem;
    window.currentSlug = s.slug;
}

window.editarServico = editarServico;

function salvarServico(e) {
    e.preventDefault();
    const id = document.getElementById('servico-id').value;
    const method = id ? 'PUT' : 'POST';
    
    const payload = {
        codigo: document.getElementById('servico-codigo').value,
        nome: document.getElementById('servico-nome').value,
        slug: window.currentSlug,
        prefixo: document.getElementById('servico-prefixo').value,
        icone: document.getElementById('servico-icone').value || '📋',
        cor: document.getElementById('servico-cor').value,
        ordem: parseInt(document.getElementById('servico-ordem').value) || 0,
        tempo_medio: parseInt(document.getElementById('servico-tempo').value) || 10
    };
    
    if (id) payload.id = parseInt(id);
    
    fetch(API_URL, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            mostrarToast(id ? 'Serviço atualizado!' : 'Serviço cadastrado!');
            fecharModal();
            listarServicos();
        } else {
            mostrarToast(data.message || 'Erro ao salvar.', 'error');
        }
    })
    .catch(() => mostrarToast('Erro na requisição.', 'error'));
}

window.salvarServico = salvarServico;

function excluirServico(id) {
    if (!confirm('Deseja realmente excluir este serviço?')) return;
    
    fetch(`${API_URL}?id=${id}`, { method: 'DELETE' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mostrarToast('Serviço excluído com sucesso!');
                listarServicos();
            } else {
                mostrarToast(data.message || 'Erro ao excluir.', 'error');
            }
        })
        .catch(() => mostrarToast('Erro ao tentar excluir.', 'error'));
}

window.excluirServico = excluirServico;
