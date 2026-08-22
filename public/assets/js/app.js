/**
 * BT Queue Enterprise - Controlador de Interface do Operador (v2.4)
 * Versão Homologada: Renderização em <li>, segurança no polling e tratamento de erro de API.
 */

document.addEventListener('DOMContentLoaded', () => {
    const $dom = {
        selectServico: document.getElementById('servico'),
        selectGuiche: document.getElementById('guiche'),
        btnChamar: document.getElementById('btnChamar'),
        btnRechamar: document.getElementById('btnRechamar'),
        btnFinalizar: document.getElementById('btnFinalizar'),
        lblOperador: document.getElementById('operadorNome'),
        lblGuiche: document.getElementById('guicheAtual'),
        lblSenhaAtual: document.getElementById('senhaAtual'),
        listaFila: document.getElementById('fila')
    };

    let atendimentoAtual = null;
    let pollingInterval = null;

    function obterParametrosAtivos() {
        return {
            servico_id: $dom.selectServico && $dom.selectServico.value ? parseInt($dom.selectServico.value, 10) : null,
            guiche_id: $dom.selectGuiche && $dom.selectGuiche.value ? parseInt($dom.selectGuiche.value, 10) : null
        };
    }

    async function atualizarPainel() {
        const params = obterParametrosAtivos();
        if (!params.servico_id) return;

        try {
            // BUG 1 CORRIGIDO: Passando parâmetros individuais
            const resultado = await BT.api.estado(
                params.servico_id,
                params.guiche_id
            );

            // Validação robusta do retorno da API
            if (!resultado || !resultado.success) {
                console.error(resultado ? resultado.message : 'Falha ao consultar estado.');
                return;
            }

            const dados = resultado.data;

            if ($dom.lblGuiche) {
                $dom.lblGuiche.textContent = dados.guiche_codigo || 'Não Selecionado';
            }
            if ($dom.lblOperador && dados.operador_nome) {
                $dom.lblOperador.textContent = dados.operador_nome;
            }

            if (dados.chamando) {
                atendimentoAtual = dados.chamando;
                if ($dom.lblSenhaAtual) $dom.lblSenhaAtual.textContent = dados.chamando.codigo;
                
                if ($dom.btnChamar) $dom.btnChamar.disabled = true;
                if ($dom.btnFinalizar) $dom.btnFinalizar.disabled = false;
                if ($dom.btnRechamar) $dom.btnRechamar.disabled = true;
            } else {
                atendimentoAtual = null;
                if ($dom.lblSenhaAtual) $dom.lblSenhaAtual.textContent = '---';
                
                if ($dom.btnChamar) $dom.btnChamar.disabled = (!params.guiche_id);
                if ($dom.btnFinalizar) $dom.btnFinalizar.disabled = true;
                if ($dom.btnRechamar) $dom.btnRechamar.disabled = true;
            }

            if ($dom.listaFila) {
                $dom.listaFila.innerHTML = '';
                if (dados.fila && dados.fila.length > 0) {
                    dados.fila.forEach(senha => {
                        // Renderização semântica usando LI dentro da UL
                        const li = document.createElement('li');
                        li.className = 'bt-fila-item d-flex justify-content-between align-items-center animate__animated animate__fadeIn';
                        li.innerHTML = `
                            <div class="bt-fila-info">
                                <span class="bt-senha-codigo">${senha.codigo}</span>
                                <span class="bt-servico-badge">${senha.servico_nome}</span>
                            </div>
                            <span class="bt-status-tag aguardando">Aguardando</span>
                        `;
                        $dom.listaFila.appendChild(li);
                    });
                } else {
                    $dom.listaFila.innerHTML = '<li class="text-center text-muted py-3" style="list-style: none;">Nenhuma senha aguardando para este serviço.</li>';
                }
            }
        } catch (erro) {
            console.error('Falha na sincronização do estado:', erro);
        }
    }

    async function acaoChamarProximo() {
        const params = obterParametrosAtivos();
        if (!params.servico_id || !params.guiche_id) {
            alert('Selecione o Serviço e o Local Físico antes de chamar.');
            return;
        }

        if ($dom.btnChamar) $dom.btnChamar.disabled = true;

        try {
            // BUG 2 CORRIGIDO: Passando parâmetros individuais
            const resposta = await BT.api.chamar(
                params.servico_id,
                params.guiche_id
            );
            
            if (resposta && resposta.success) {
                await atualizarPainel();
            } else {
                alert(resposta.message || 'Nenhuma senha disponível.');
                if ($dom.btnChamar) $dom.btnChamar.disabled = false;
            }
        } catch (erro) {
            console.error('Erro ao chamar próxima senha:', erro);
            if ($dom.btnChamar) $dom.btnChamar.disabled = false;
        }
    }

    async function acaoFinalizarAtendimento() {
        if (!atendimentoAtual || !atendimentoAtual.id) return;
        if ($dom.btnFinalizar) $dom.btnFinalizar.disabled = true;

        try {
            const resposta = await BT.api.finalizar(atendimentoAtual.id);
            if (resposta && resposta.success) {
                atendimentoAtual = null;
                await atualizarPainel();
            } else {
                alert('Não foi possível finalizar o atendimento.');
                if ($dom.btnFinalizar) $dom.btnFinalizar.disabled = false;
            }
        } catch (erro) {
            console.error('Erro ao finalizar atendimento:', erro);
            if ($dom.btnFinalizar) $dom.btnFinalizar.disabled = false;
        }
    }

    function iniciarConfiguracao() {
        if ($dom.btnChamar) $dom.btnChamar.addEventListener('click', acaoChamarProximo);
        if ($dom.btnFinalizar) $dom.btnFinalizar.addEventListener('click', acaoFinalizarAtendimento);

        if ($dom.selectServico) $dom.selectServico.addEventListener('change', atualizarPainel);
        if ($dom.selectGuiche) $dom.selectGuiche.addEventListener('change', atualizarPainel);

        // Bloqueio visual e informativo do botão Rechamar (Temporário)
        if ($dom.btnRechamar) {
            $dom.btnRechamar.disabled = true;
            $dom.btnRechamar.title = 'Função em desenvolvimento';
        }

        atualizarPainel();

        // Evita múltiplos loops concorrentes de setInterval
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
        pollingInterval = setInterval(atualizarPainel, 3000);
    }

    iniciarConfiguracao();
});
