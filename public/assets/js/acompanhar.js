window.BT = window.BT || {};

BT.acompanhar = {

    async atualizar() {

        const uuid = new URLSearchParams(window.location.search).get("uuid");

        if (!uuid) return;

        try {

            const resposta = await fetch(
                "api/acompanhar.php?uuid=" + encodeURIComponent(uuid)
            );

            const json = await resposta.json();

            if (!json.success) return;

            const d = json.data;

            document.getElementById("senha").textContent = d.senha;

            document.getElementById("status").textContent = d.mensagem;

            document.getElementById("posicao").textContent = d.posicao;

            document.getElementById("tempo").textContent =
                d.tempo_estimado + " min";

            document.getElementById("guiche").textContent =
                d.guiche
                    ? "Guichê " + d.guiche
                    : "Aguardando chamada...";

        } catch (e) {

            console.error(e);

        }

    },

    iniciar() {

        this.atualizar();

        setInterval(() => {

            this.atualizar();

        }, 3000);

    }

};

document.addEventListener("DOMContentLoaded", () => {

    BT.acompanhar.iniciar();

});
