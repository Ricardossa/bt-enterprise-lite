BT.guiches = {

    async carregar() {

        const resposta = await fetch("api/guiches.php");
        const json = await resposta.json();
        const tbody = document.getElementById("listaGuiches");
        tbody.innerHTML = "";

        json.forEach(g => {
            tbody.innerHTML += `
                <tr>
                    <td><strong>${g.codigo}</strong></td>
                    <td><i class="fa-solid fa-chair" style="color:var(--secondary); margin-right:10px;"></i> ${g.nome}</td>
                    <td>
                        <button class="bt-button bt-warning" onclick="BT.guiches.editar(${g.id})">✏ Configurar</button>
                        <button class="bt-button bt-danger" onclick="BT.guiches.excluir(${g.id})">🗑 Remover</button>
                    </td>
                </tr>`;
        });
    },

    novo() {
        BT.modal.abrir(
            "Adicionar Nova Cadeira",
            `
                <label>Número/ID da Cadeira</label>
                <input id="novoCodigo" class="form-control" placeholder="Ex: 01">
                <br>
                <label>Descrição da Estação</label>
                <input id="novoNome" class="form-control" placeholder="Ex: Cadeira Principal - Ricardo">
            `,
            async () => {
                const codigo = document.getElementById("novoCodigo").value.trim();
                const nome = document.getElementById("novoNome").value.trim();
                const resposta = await fetch("api/guiches.php",{
                    method:"POST",
                    headers:{ "Content-Type":"application/json" },
                    body:JSON.stringify({ codigo, nome })
                });
                const json = await resposta.json();
                if(json.success){
                    BT.modal.fechar();
                    BT.guiches.carregar();
                }else{
                    alert(json.message || "Erro ao salvar.");
                }
            }
        );
    },

    editar(id){
        fetch("api/guiches.php")
        .then(r => r.json())
        .then(json => {
            const guiche = json.find(g => g.id == id);
            if(!guiche) return alert("Cadeira não encontrada.");

            BT.modal.abrir(
                "Configurar Estação",
                `
                    <label>Número/ID</label>
                    <input id="novoCodigo" class="form-control" value="${guiche.codigo}">
                    <br>
                    <label>Descrição</label>
                    <input id="novoNome" class="form-control" value="${guiche.nome}">
                `,
                async ()=>{
                    const codigo=document.getElementById("novoCodigo").value.trim();
                    const nome=document.getElementById("novoNome").value.trim();

                    const resp=await fetch("api/guiches.php",{
                        method:"PUT",
                        headers:{ "Content-Type":"application/json" },
                        body:JSON.stringify({
                            id:guiche.id,
                            codigo,
                            nome,
                            icone:guiche.icone,
                            cor:guiche.cor
                        })
                    });
                    const jsonResp=await resp.json();
                    if(jsonResp.success){
                        BT.modal.fechar();
                        BT.guiches.carregar();
                    }else{
                        BT.toast.erro(jsonResp.message || "Erro ao editar.");
                    }
                }
            );
        });
    },

    async excluir(id){

        if(!confirm("Deseja realmente excluir este guichê?")){
            return;
        }

        const resp = await fetch("api/guiches.php",{

            method:"DELETE",

            headers:{
                "Content-Type":"application/json"
            },

            body:JSON.stringify({
                id:id
            })

        });

        const json = await resp.json();

        if(json.success){

            BT.guiches.carregar();

        }else{

            alert(json.message || "Erro ao excluir.");

        }

    }

};

document.addEventListener("DOMContentLoaded",()=>{

    BT.guiches.carregar();

    document.getElementById("btnNovo").onclick=()=>{

        BT.guiches.novo();

    };

});
