BT.modal = {

    abrir(titulo, html, callbackSalvar) {

        this.fechar();

        const fundo = document.createElement("div");
        fundo.id = "btModal";

        fundo.style = `
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.55);
            display:flex;
            align-items:center;
            justify-content:center;
            z-index:99999;
        `;

        fundo.innerHTML = `
            <div style="
                width:420px;
                max-height: 90vh;
                overflow-y: auto;
                background:#132238;
                color:#FFF;
                border-radius:12px;
                padding:20px;
                box-shadow:0 10px 35px rgba(0,0,0,.45);
            ">

                <h2 style="margin-bottom:20px;">${titulo}</h2>

                ${html}

                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">

                    <button id="btCancelar" class="bt-button bt-danger">
                        Cancelar
                    </button>

                    <button id="btSalvar" class="bt-button bt-success">
                        Salvar
                    </button>

                </div>

            </div>
        `;

        document.body.appendChild(fundo);

        document.getElementById("btCancelar").onclick = () => this.fechar();

        document.getElementById("btSalvar").onclick = callbackSalvar;

    },

    fechar() {

        const modal = document.getElementById("btModal");

        if (modal) {

            modal.remove();

        }

    }

};
