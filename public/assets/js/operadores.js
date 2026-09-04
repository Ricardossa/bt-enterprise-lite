BT.operadores = {

    apiEndpoint: 'api/operadores.php',

    init() {
        this.carregar();
        this.registrarEventos();
    },

    registrarEventos() {
        const btn = document.getElementById('btnNovo');
        if(btn){
            btn.removeEventListener('click', this.novo);
            btn.addEventListener('click', () => this.novo());
        }
    },

    async carregar(){
        const grid = document.getElementById('listaOperadores');
        if(!grid) return;

        try{
            const resposta = await fetch(this.apiEndpoint);
            const json = await resposta.json();
            if(!json.success){
                BT.toast.erro(json.message);
                return;
            }
            this.renderizar(json.data);
        }catch(e){ console.error(e); }
    },

    renderizar(lista){
        const grid = document.getElementById('listaOperadores');
        if(!grid) return;

        if(lista.length === 0){
            grid.innerHTML = '<div class="bt-card" style="grid-column: 1/-1; text-align:center;">Nenhum profissional cadastrado.</div>';
            return;
        }

        grid.innerHTML = lista.map(op => {
            // v3.5.9: Inteligência de Busca de Foto (Tenant Safe)
            let foto = 'https://cdn-icons-png.flaticon.com/512/147/147144.png';
            if (op.foto_url) {
                foto = `uploads/${op.foto_url.replace('uploads/', '')}?v=${Date.now()}`;
            }

            const isInativo = op.ativo == 0;
            const btnToggleLabel = isInativo ? 'REATIVAR' : 'DESATIVAR';
            const btnToggleIcon = isInativo ? 'fa-check-circle' : 'fa-power-off';
            const btnClass = isInativo ? 'op-btn-edit' : 'op-btn-delete'; // Reaproveitando estilos

            let statusBadge = '';
            if (!isInativo) {
                const statusColor = op.status === 'ONLINE' ? 'var(--success)' : (op.status === 'BREAK' ? 'var(--warning)' : 'var(--text3)');
                statusBadge = `<span class="badge" style="background:${statusColor}; color:#000; font-size:9px; margin-left:10px;">${op.status}</span>`;
            } else {
                statusBadge = `<span class="badge" style="background:#666; color:#fff; font-size:9px; margin-left:10px;">INATIVO</span>`;
            }

            return `
            <div class="op-card animate__animated animate__fadeIn" style="${isInativo ? 'opacity:0.6; filter:grayscale(0.5);' : ''}">
                <div class="op-header">
                    <img src="${foto}" onerror="this.src='https://cdn-icons-png.flaticon.com/512/147/147144.png'" style="width:50px; height:50px; border-radius:50%; border:2px solid ${isInativo ? '#666' : 'var(--secondary)'}; object-fit:cover;">
                    <div class="op-info">
                        <h3 class="op-name">${op.nome} ${statusBadge}</h3>
                        <span class="op-login">@${op.login} | <b style="color:var(--text2)">ID: ${op.id}</b></span>
                    </div>
                </div>

                <div class="op-badges">
                    <div class="op-badge">
                        <i class="fa-solid fa-chair"></i> Cadeira: <b>${op.guiche || 'Não definida'}</b>
                    </div>
                    <div class="op-badge">
                        <i class="fa-solid fa-scissors"></i> Especialidades: <b>${op.total_especialidades || 0} itens</b>
                    </div>
                </div>

                <div class="op-actions">
                    <button class="op-btn op-btn-edit" onclick="BT.operadores.editar(${op.id})">
                        <i class="fa-solid fa-pen-to-square"></i> CONFIGURAR
                    </button>
                    <button class="op-btn ${btnClass}" style="${isInativo ? 'background:rgba(24, 201, 100, 0.1); color:var(--success); border-color:var(--success);' : ''}" onclick="BT.operadores.excluir(${op.id})">
                        <i class="fa-solid ${btnToggleIcon}"></i> ${btnToggleLabel}
                    </button>
                </div>
            </div>`;
        }).join('');
    },

    novo(){
        BT.modal.abrir('Novo Profissional', this.formulario(), () => this.salvar('POST'));
        setTimeout(() => this.carregarCombos(), 100);
    },

    async editar(id){
        try{
            const resposta = await fetch(`${this.apiEndpoint}?id=${id}`);
            const json = await resposta.json();
            if(!json.success) return BT.toast.erro(json.message);

            BT.modal.abrir('Configurar Profissional', this.formulario(json.data), () => this.salvar('PUT'));
            setTimeout(() => this.carregarCombos(json.data.servico_id, json.data.guiche_id, json.data.servicos_especiais || []), 100);
        }catch(e){ console.error(e); }
    },

    async salvar(metodo){
        const id = document.getElementById('op_id')?.value || '';

        // v1.3.3: Lê os serviços marcados nos checkboxes
        const checkboxes = document.querySelectorAll('.chk-servico:checked');
        const servicosEspeciais = Array.from(checkboxes).map(chk => parseInt(chk.value));

        const fileInput = document.getElementById('op_foto');

        const dados = {
            id: id,
            nome: document.getElementById('op_nome').value.trim(),
            login: document.getElementById('op_login').value.trim(),
            senha: document.getElementById('op_senha').value,
            comissao: document.getElementById('op_comissao').value, // [NOVO]
            prefixo: document.getElementById('op_prefixo').value.trim().toUpperCase(),
            servico_id: servicosEspeciais[0] || 0,
            guiche_id: document.getElementById('op_guiche').value,
            servicos_especiais: servicosEspeciais,
            ativo: document.getElementById('op_ativo').checked ? 1 : 0
        };

        try{
            const resposta = await fetch(this.apiEndpoint, {
                method: metodo,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
            const json = await resposta.json();

            if(json.success){
                const finalId = json.id || id;

                // Faz upload da foto se houver
                if (fileInput.files.length > 0) {
                    const formData = new FormData();
                    formData.append('logo', fileInput.files[0]);
                    formData.append('tipo', 'operador');
                    formData.append('id', finalId);

                    try {
                        const uploadRes = await fetch('api/upload_logo.php', { method: 'POST', body: formData });
                        const uploadJson = await uploadRes.json();

                        if (uploadJson.success) {
                            const fotoPath = uploadJson.arquivo.replace('uploads/', '');
                            await fetch('api/operadores.php', {
                                method: 'POST',
                                body: JSON.stringify({ id: finalId, foto_url: fotoPath })
                            });
                        }
                    } catch (e) { console.error("Erro no upload:", e); }
                }

                BT.modal.fechar();
                BT.toast.sucesso('Profissional salvo!');
                this.carregar();
            } else {
                BT.toast.erro(json.message);
            }
        }catch(e){ console.error(e); }
    },

    async excluir(id){
        if(!confirm('Deseja alterar o status administrativo deste profissional?')) return;
        try{
           const resposta = await fetch(this.apiEndpoint, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const json = await resposta.json();
            if(json.success) {
                BT.toast.sucesso('Removido.');
                this.carregar();
            } else {
                BT.toast.erro(json.message);
            }
        }catch(e){ console.error(e); }
    },

    async carregarCombos(servicoSelecionado='', guicheSelecionado='', servicosEspeciais=[]){
        try{
            const [resServicos, resGuiches] = await Promise.all([
                fetch('api/servicos.php'),
                fetch('api/guiches.php')
            ]);
            const jsServicos = await resServicos.json();
            const jsGuiches = await resGuiches.json();

            const cmbEspeciais = document.getElementById('lista-checkbox-servicos');
            const cmbGuiche = document.getElementById('op_guiche');

            if(cmbEspeciais){
                cmbEspeciais.innerHTML = '';
                (jsServicos.data||[]).forEach(s => {
                    const checked = servicosEspeciais.includes(s.id) ? 'checked' : '';
                    cmbEspeciais.innerHTML += `
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:5px; background:rgba(255,255,255,0.02); border-radius:5px;">
                            <input type="checkbox" class="chk-servico" value="${s.id}" ${checked}>
                            <span>${s.icone} ${s.nome} <small style="color:var(--success); font-weight:bold;">(R$ ${parseFloat(s.preco).toFixed(2)})</small></span>
                        </label>
                    `;
                });
            }

            if(cmbGuiche){
                cmbGuiche.innerHTML = '<option value="">Cadeira...</option>';
                (jsGuiches||[]).forEach(g => {
                    const o = document.createElement('option');
                    o.value = g.id; o.textContent = g.nome;
                    if(String(g.id) === String(guicheSelecionado)) o.selected = true;
                    cmbGuiche.appendChild(o);
                });
            }
        }catch(e){ console.error(e); }
    },

    formulario(dados={}){
        // v3.5.9: Inteligência de Busca de Foto (Tenant Safe)
        let foto = 'https://cdn-icons-png.flaticon.com/512/147/147144.png';
        if (dados.foto_url) {
            foto = `uploads/${dados.foto_url.replace('uploads/', '')}?v=${Date.now()}`;
        }

        return `
        <form id="formOperador">
            <input type="hidden" id="op_id" value="${dados.id||''}">

            <div style="text-align:center; margin-bottom:20px;">
                <img id="op_foto_preview" src="${foto}" style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:10px; border:3px solid var(--secondary);">
                <input type="file" id="op_foto" class="form-control" accept="image/*" style="font-size:11px;">
                <small class="text-muted" style="display:block; margin-top:5px; font-size:9px;">
                    <b>Recomendado:</b> 400 × 400 px (1:1). Centralize o rosto para o corte circular. Formatos: JPG ou WebP.
                </small>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group"><label>Nome do Barbeiro</label>
                    <input id="op_nome" class="form-control" value="${dados.nome||''}" required></div>
                <div class="form-group"><label>Prefixo Senha</label>
                    <input id="op_prefixo" class="form-control" value="${dados.prefixo||''}" maxlength="2" placeholder="Ex: R"></div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group"><label>Login</label>
                    <input id="op_login" class="form-control" value="${dados.login||''}" required></div>
                <div class="form-group"><label>Senha</label>
                    <input id="op_senha" type="password" class="form-control" placeholder="${dados.id?'Manter atual':'Criar senha'}"></div>
            </div>

            <div class="form-group">
                <label>Cadeira / Estação de Trabalho</label>
                <select id="op_guiche" class="form-control"></select>
            </div>

            <div class="form-group">
                <label style="color:var(--success); font-weight:bold;">💰 Porcentagem de Comissão (%)</label>
                <input id="op_comissao" type="number" step="0.5" class="form-control" value="${dados.comissao || '50.00'}" placeholder="Ex: 50">
                <small class="text-muted">Quanto o barbeiro recebe por serviço.</small>
            </div>

            <div class="form-group">
                <label style="color:var(--secondary); font-weight:900;">📋 ESPECIALIDADES DO BARBEIRO</label>
                <div style="background:rgba(0,0,0,0.2); padding:12px; border-radius:12px; border:1px solid var(--border); max-height:180px; overflow-y:auto;">
                    <div id="lista-checkbox-servicos" style="display:grid; grid-template-columns:1fr; gap:8px; text-align:left;">
                        <!-- Checkboxes injetados via JS -->
                    </div>
                </div>
                <small class="text-muted" style="display:block; margin-top:5px;">💡 Serviços que este barbeiro realiza.</small>
            </div>

            <div class="form-group" style="margin-top:10px;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:normal;">
                    <input id="op_ativo" type="checkbox" ${(dados.ativo==0)?'':'checked'}>
                    <span>Barbeiro Ativo</span>
                </label>
            </div>
        </form>`;
    }
};

document.addEventListener('DOMContentLoaded', () => BT.operadores.init());
