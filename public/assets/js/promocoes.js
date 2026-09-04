window.BT = window.BT || {};

BT.promocoes = {
    api: "api/promocoes.php",
    uploadApi: "api/upload_promocao.php",

    async carregar() {
        const tbody = document.getElementById("listaPromocoes");
        if (!tbody) return;
        tbody.innerHTML = "<tr><td colspan='3'>Carregando...</td></tr>";

        try {
            const resp = await fetch(this.api);
            const json = await resp.json();

            if (!json.success) {
                tbody.innerHTML = "<tr><td colspan='3'>Erro ao carregar.</td></tr>";
                return;
            }

            tbody.innerHTML = "";
            json.data.forEach(item => {
                tbody.innerHTML += `
                    <tr>
                        <td>${item.ordem}</td>
                        <td>${item.titulo}</td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="BT.promocoes.editar(${item.id})">
                                <i class="fa-solid fa-pen"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="BT.promocoes.excluir(${item.id})">
                                <i class="fa-solid fa-trash"></i> Excluir
                            </button>
                        </td>
                    </tr>
                `;
            });
        } catch (e) {
            tbody.innerHTML = "<tr><td colspan='3'>Falha de conexão.</td></tr>";
        }
    },

    async uploadImagem(file) {
        const form = new FormData();
        form.append("imagem", file);
        const resp = await fetch(this.uploadApi, { method: "POST", body: form });
        const json = await resp.json();
        if (!json.success) {
            alert(json.message);
            return "";
        }
        return json.arquivo;
    },

    novo() {
        BT.modal.abrir(
            "Nova Promoção",
            `
            <label>Título</label>
            <input id="titulo" class="form-control">
            <br>
            <label>Preço</label>
            <input id="preco" class="form-control" placeholder="Ex.: R$ 18,90">
            <br>
            <label>Descrição</label>
            <input id="descricao" class="form-control">
            <br>
            <label>Subir Arquivo</label>
            <input id="arquivoImagem" type="file" accept=".png,.jpg,.jpeg,.webp" class="form-control">
            <small class="text-muted" style="display:block; margin-top:5px; font-size:10px;">
                <b>Recomendado:</b> 600 × 400 px (3:2). Formatos: JPG ou WebP.
            </small>
            <br>
            <label>Ordem</label>
            <input id="ordem" type="number" value="0" class="form-control">
            `,
            async () => {
                const file = document.getElementById("arquivoImagem").files[0];
                let imagem = "";

                if (file) {
                    imagem = await this.uploadImagem(file);
                    if (imagem === "") return;
                }

                const resp = await fetch(this.api, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        titulo: document.getElementById("titulo").value,
                        descricao: document.getElementById("descricao").value,
                        preco: document.getElementById("preco").value,
                        imagem: imagem,
                        ordem: parseInt(document.getElementById("ordem").value) || 0
                    })
                });
                const json = await resp.json();
                if (json.success) {
                    BT.modal.fechar();
                    this.carregar();
                }
            }
        );
    },

    async editar(id) {
        const resp = await fetch(this.api + "?id=" + id);
        const json = await resp.json();
        if (!json.success) return alert("Promoção não encontrada.");

        const p = json.data;
        BT.modal.abrir(
            "Editar Promoção",
            `
            <label>Título</label>
            <input id="titulo" class="form-control" value="${p.titulo}">
            <br>
            <label>Preço</label>
            <input id="preco" class="form-control" value="${p.preco || ''}">
            <br>
            <label>Descrição</label>
            <input id="descricao" class="form-control" value="${p.descricao}">
            <br>
            <label>Mudar Imagem (Opcional)</label>
            <input id="arquivoImagemEdit" type="file" accept=".png,.jpg,.jpeg,.webp" class="form-control">
            <small class="text-muted" style="display:block; margin-top:5px; font-size:10px;">
                <b>Recomendado:</b> 600 × 400 px. Formatos: JPG ou WebP.
            </small>
            <br>
            <label>Ordem</label>
            <input id="ordem" type="number" class="form-control" value="${p.ordem}">
            `,
            async () => {
                const file = document.getElementById("arquivoImagemEdit").files[0];
                let imagem = p.imagem;

                if (file) {
                    const novaImg = await this.uploadImagem(file);
                    if (novaImg) imagem = novaImg;
                }

                const salvar = await fetch(this.api, {
                    method: "PUT",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        id: id,
                        titulo: document.getElementById("titulo").value,
                        descricao: document.getElementById("descricao").value,
                        preco: document.getElementById("preco").value,
                        imagem: imagem,
                        ordem: parseInt(document.getElementById("ordem").value) || 0
                    })
                });

                const retorno = await salvar.json();
                if (retorno.success) {
                    BT.modal.fechar();
                    this.carregar();
                } else {
                    alert(retorno.message);
                }
            }
        );
    },

    async excluir(id) {
        if (!confirm("Excluir esta promoção?")) return;
        try {
            const resp = await fetch(this.api, {
                method: "DELETE",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id })
            });
            const json = await resp.json();
            if (json.success) {
                this.carregar();
                if (BT.toast) BT.toast.sucesso("Excluída!");
            } else { alert(json.message); }
        } catch (e) { alert("Erro de conexão."); }
    },

    async configVisual() {
        console.log("🔍 Abrindo Configuração Visual...");
        const resp = await fetch("api/configuracoes.php");
        const json = await resp.json();
        const config = json.data || {};

        console.log("📦 Configs carregadas:", config);

        const btnRemoveLogo = (config.promo_logo && config.promo_logo !== "") ?
            `<button type="button" onclick="BT.promocoes.removerLogo('promo_logo')" class="bt-button bt-danger" style="margin-top:10px; padding:5px 12px; font-size:10px; height:auto; width:auto;"><i class="fa-solid fa-trash"></i> Remover Logo Atual</button>` : '';

        const btnRemoveCampanha = (config.promo_campanha && config.promo_campanha !== "") ?
            `<button type="button" onclick="BT.promocoes.removerLogo('promo_campanha')" class="bt-button bt-danger" style="margin-top:10px; padding:5px 12px; font-size:10px; height:auto; width:auto;"><i class="fa-solid fa-trash"></i> Remover Banner Atual</button>` : '';

        BT.modal.abrir(
            "Identidade Mobile Premium",
            `
            <div style="background:var(--sidebar); padding:15px; border-radius:10px; margin-bottom:20px; border:1px solid var(--border);">
                <label style="color:var(--secondary); font-weight:bold;">1. BANNER SUPERIOR (TOQUE)</label>
                <p style="font-size:10px; color:#888;">Aparece no topo das telas mobile.</p>
                <input id="arquivoLogoMobile" type="file" accept=".png,.jpg,.jpeg,.webp" class="form-control">
                <small class="text-muted" style="display:block; margin-top:5px; font-size:9px;">
                    <b>Recomendado:</b> 400 × 130 px (Horizontal), fundo transparente. PNG ou WebP.
                </small>
                ${btnRemoveLogo}
            </div>

            <div style="background:var(--sidebar); padding:15px; border-radius:10px; border:1px solid var(--border);">
                <label style="color:var(--success); font-weight:bold;">2. BANNER DE CAMPANHA (RODAPÉ)</label>
                <p style="font-size:10px; color:#888;">Banner estático no final da tela de senha.</p>
                <input id="arquivoCampanha" type="file" accept=".png,.jpg,.jpeg,.webp" class="form-control">
                <small class="text-muted" style="display:block; margin-top:5px; font-size:9px;">
                    <b>Recomendado:</b> 1080 × 250 px (Faixa). Formatos: JPG ou WebP.
                </small>
                ${btnRemoveCampanha}
            </div>
            `,
            async () => {
                const fileLogo = document.getElementById("arquivoLogoMobile").files[0];
                const fileCampanha = document.getElementById("arquivoCampanha").files[0];

                if (!fileLogo && !fileCampanha) return alert("Selecione pelo menos um arquivo.");

                // Upload Logo Mobile
                if (fileLogo) {
                    const form = new FormData();
                    form.append("logo", fileLogo);
                    form.append("tipo", "mobile");
                    const upload = await fetch("api/upload_logo.php", { method: "POST", body: form });
                    const res = await upload.json();
                    if (res.success) {
                        await fetch("api/configuracoes.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({ "promo_logo": res.arquivo })
                        });
                    }
                }

                // Upload Campanha
                if (fileCampanha) {
                    const form = new FormData();
                    form.append("logo", fileCampanha);
                    form.append("tipo", "campaign");
                    const upload = await fetch("api/upload_logo.php", { method: "POST", body: form });
                    const res = await upload.json();
                    if (res.success) {
                        await fetch("api/configuracoes.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({ "promo_campanha": res.arquivo })
                        });
                    }
                }

                alert("Identidade Visual Atualizada!");
                location.reload();
            }
        );
    },

    async removerLogo(chave) {
        if (!confirm("Deseja remover esta imagem da identidade mobile?")) return;
        try {
            const resp = await fetch("api/configuracoes.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ [chave]: "" })
            });
            const json = await resp.json();
            if (json.success) {
                alert("Imagem removida!");
                location.reload();
            }
        } catch (e) { alert("Erro ao remover."); }
    }
};

document.addEventListener("DOMContentLoaded", () => {
    BT.promocoes.carregar();
    const btnNovo = document.getElementById("btnNovo");
    if(btnNovo) btnNovo.addEventListener("click", () => BT.promocoes.novo());
    const btnConfig = document.getElementById("btnConfigVisual");
    if(btnConfig) btnConfig.addEventListener("click", () => BT.promocoes.configVisual());
});
