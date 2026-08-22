BT.tv = {
    ultimaSenhaFalada: "",
    vozHabilitada: true,

    async atualizar() {

        try {

            const resposta = await fetch("api/estado.php");

            const json = await resposta.json();

            if (!json.success) return;

            const dados = json.data;

            document.getElementById("tvSenha").textContent =
                dados.chamando ? dados.chamando.codigo : "---";

            document.getElementById("tvGuiche").textContent =
                dados.chamando ? "Guichê " + dados.chamando.guiche : "Guichê --";

            if (
                this.vozHabilitada &&
                dados.chamando &&
                dados.chamando.codigo &&
                dados.chamando.codigo !== this.ultimaSenhaFalada
            ) {

                this.ultimaSenhaFalada = dados.chamando.codigo;

                speechSynthesis.cancel();

                const voz = new SpeechSynthesisUtterance(
                    "Senha " +
                    dados.chamando.codigo +
                    ", dirigir-se ao guichê " +
                    dados.chamando.guiche
                );

                voz.lang = "pt-BR";
                voz.rate = 0.95;

                speechSynthesis.speak(voz);

            }

            const fila = document.getElementById("tvFila");

            fila.innerHTML = "";

            (dados.fila || []).forEach(item => {

                fila.innerHTML += `
                    <li>${item.codigo}</li>
                `;

            });

        } catch (e) {

            console.error("Erro ao atualizar TV:", e);

        }

    }

};

document.addEventListener("DOMContentLoaded", () => {

    BT.tv.atualizar();

    setInterval(() => {

        BT.tv.atualizar();

    }, 2000);

});
