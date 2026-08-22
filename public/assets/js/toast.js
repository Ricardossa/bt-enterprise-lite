BT.toast = {

    mostrar(mensagem, tipo = "sucesso") {

        const antigo = document.getElementById("btToast");

        if (antigo) {
            antigo.remove();
        }

        const toast = document.createElement("div");

        toast.id = "btToast";

        toast.className = "bt-toast " + tipo;

        toast.textContent = mensagem;

        document.body.appendChild(toast);

        setTimeout(() => {

            toast.classList.add("mostrar");

        }, 20);

        setTimeout(() => {

            toast.classList.remove("mostrar");

            setTimeout(() => toast.remove(), 300);

        }, 3000);

    },

    sucesso(msg){

        this.mostrar(msg,"sucesso");

    },

    erro(msg){

        this.mostrar(msg,"erro");

    },

    aviso(msg){

        this.mostrar(msg,"aviso");

    },

    info(msg){

        this.mostrar(msg,"info");

    }

};
