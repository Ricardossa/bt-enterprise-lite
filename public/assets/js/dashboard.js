BT.dashboard = {

    async atualizar() {

        try {

            const json = await BT.api.estado(2, 6);

            if (!json.success) return;

            const e = json.data.estatisticas;

            document.getElementById("emitidas").textContent = e.emitidas;
            document.getElementById("chamadas").textContent = e.atendimento;
            document.getElementById("finalizadas").textContent = e.finalizadas;
            document.getElementById("filaAtual").textContent = e.pendentes;

        } catch (erro) {

            console.error(erro);

        }

    },

    iniciar() {

        this.atualizar();

        setInterval(() => this.atualizar(), 2000);

    }

};

document.addEventListener("DOMContentLoaded", () => {

    BT.dashboard.iniciar();

});
