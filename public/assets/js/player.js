/**
 * BT Queue Enterprise - Controlador da TV Pública (v2.0)
 * Consome o endpoint global de transmissão em tempo real de forma ultra leve.
 */

BT.tv = {
    // Memoriza a última senha exibida para evitar disparos repetidos de som/alerta
    ultimaSenhaExibida: null,

    async atualizar() {
        try {
            // Consome a nova API global que você projetou
            const resposta = await fetch("api/ultima_chamada.php");
            
            if (!resposta.ok) return;
            const json = await respuesta.json();
            if (!json || !json.success || !json.data) return;

            const dados = json.data;

            // 1. Atualiza o Painel de Chamada Principal (Hero)
            if (dados.chamando) {
                const painelSenha = document.getElementById("tvSenha");
                const painelGuiche = document.getElementById("tvGuiche");

                if (painelSenha) painelSenha.textContent = dados.chamando.senha;
                
                // Exibe o nome amigável do Local Físico ("Farmácia", "Vacina")
                if (painelGuiche) painelGuiche.textContent = dados.chamando.local;

                // Efeito sonoro / Chamada por voz quando uma senha nova de fato entrar
                if (dados.chamando.senha !== this.ultimaSenhaExibida) {
                    this.ultimaSenhaExibida = dados.chamando.senha;
                    this.notificarChamadaNova(dados.chamando);
                }
            } else {
                if (document.getElementById("tvSenha")) document.getElementById("tvSenha").textContent = "---";
                if (document.getElementById("tvGuiche")) document.getElementById("tvGuiche").textContent = "Aguardando";
            }

            // 2. Renderiza o Histórico Lateral de Senhas Anteriores
            const containerHistorico = document.getElementById("tvFila");
            if (containerHistorico) {
                containerHistorico.innerHTML = "";
                
                if (dados.historico && dados.historico.length > 0) {
                    dados.historico.forEach(item => {
                        const li = document.createElement('li');
                        li.className = 'animate__animated animate__slideInRight';
                        li.innerHTML = `<strong>${item.senha}</strong> <small>${item.local}</small>`;
                        containerHistorico.appendChild(li);
                    });
                }
            }

        } catch (e) {
            console.error("Falha cíclica na atualização da TV:", e);
        }
    },

    notificarChamadaNova(chamando) {
        // Log ou chamada de sintetizador de voz (SpeechSynthesis) futuramente
        console.log(`Nova senha na TV: ${chamando.senha} em direção a: ${chamando.local}`);
    }
};

document.addEventListener("DOMContentLoaded", () => {
    // Executa imediatamente no carregamento da tela
    BT.tv.atualizar();

    // Mantém o Polling leve a cada 2 segundos atualizando a transmissão global
    setInterval(() => {
        BT.tv.atualizar();
    }, 2000);
});
