document.addEventListener("DOMContentLoaded", async () => {

    const empresa = document.getElementById("empresa");
    const label_cliente = document.getElementById("label_cliente");
    const wa_enabled = document.getElementById("whatsapp_enabled");
    const wa_url = document.getElementById("whatsapp_api_url");
    const wa_token = document.getElementById("whatsapp_api_token");
    const prio_mode = document.getElementById("priority_mode");
    const prio_ratio = document.getElementById("priority_ratio");
    const prio_selection = document.getElementById("feature_priority_selection");
    const pay_strategy = document.getElementById("payment_strategy"); // v3.3.5-SaaS
    const release_mode = document.getElementById("booking_release_mode"); // v3.3.5-SaaS

    // [LITE v3.3.5] Controle Dinâmico de Caixas de Pagamento
    const togglePaymentBoxes = () => {
        if(!pay_strategy) return;
        const val = pay_strategy.value;
        const boxMP = document.getElementById('box-mercadopago');
        const boxManual = document.querySelector('div[style*="rgba(255,255,255,0.02)"]'); // Seleciona a caixa de PIX Direto

        if (boxMP) boxMP.style.display = (val === 'mercadopago') ? 'block' : 'none';
        if (boxManual) boxManual.style.display = (val === 'manual') ? 'block' : 'none';
    };

    if (pay_strategy) pay_strategy.addEventListener('change', togglePaymentBoxes);

    const mp_token = document.getElementById("mercadopago_token"); // v1.1.0-LITE
    const pix_chave = document.getElementById("pix_chave_estatica"); // v2.8.5-LITE
    const mp_test = document.getElementById("mercadopago_test_mode"); // v1.3.0-LITE
    const botao = document.getElementById("btnSalvar");

    // Carrega configurações existentes
    try {
        const resposta = await fetch("api/configuracoes.php");
        const json = await resposta.json();

        if (json.success && json.data) {
            empresa.value = json.data.empresa || "";
            if (label_cliente) label_cliente.value = json.data.label_cliente || "Paciente";
            if (wa_enabled) wa_enabled.value = json.data.whatsapp_enabled || "0";
            if (wa_url) wa_url.value = json.data.whatsapp_api_url || "";
            if (wa_token) wa_token.value = json.data.whatsapp_api_token || "";
            if (prio_mode) prio_mode.value = json.data.priority_mode || "STRICT";
            if (prio_ratio) prio_ratio.value = json.data.priority_ratio || "3";
            if (prio_selection) prio_selection.value = json.data.feature_priority_selection || "1";
            if (pay_strategy) pay_strategy.value = json.data.payment_strategy || "mercadopago";
            if (release_mode) release_mode.value = json.data.booking_release_mode || "immediate";
            if (mp_token) mp_token.value = json.data.mercadopago_token || ""; // v1.1.0-LITE
            if (pix_chave) pix_chave.value = json.data.pix_chave_estatica || ""; // v2.8.5-LITE
            if (mp_test) mp_test.value = json.data.mercadopago_test_mode || "0"; // v1.3.0-LITE

            // Toggle inicial da proporção
            const ratioContainer = document.getElementById('priority_ratio_container');
            if (ratioContainer && prio_mode) {
                ratioContainer.style.display = (prio_mode.value === 'BALANCED') ? 'block' : 'none';
            }

        }
    } catch (e) {
        console.error("Erro ao carregar configurações.", e);
    }

    if (!botao) return;

    if (prio_mode) {
        prio_mode.addEventListener('change', () => {
            const container = document.getElementById('priority_ratio_container');
            if (container) container.style.display = (prio_mode.value === 'BALANCED') ? 'block' : 'none';
        });
    }

    botao.addEventListener("click", async () => {
        try {
            // 1. Salva Nome da Empresa, Rótulo e WhatsApp (v6.6)
            const resposta = await fetch("api/configuracoes.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    empresa: empresa.value.trim(),
                    label_cliente: label_cliente ? label_cliente.value.trim() : "Paciente",
                    whatsapp_enabled: wa_enabled ? wa_enabled.value : "0",
                    whatsapp_api_url: wa_url ? wa_url.value.trim() : "",
                    whatsapp_api_token: wa_token ? wa_token.value.trim() : "",
                    priority_mode: prio_mode ? prio_mode.value : "STRICT",
                    priority_ratio: prio_ratio ? prio_ratio.value : "3",
                    feature_priority_selection: prio_selection ? prio_selection.value : "1",
                    payment_strategy: pay_strategy ? pay_strategy.value : "mercadopago",
                    booking_release_mode: release_mode ? release_mode.value : "immediate",
                    mercadopago_token: mp_token ? mp_token.value.trim() : "", // v1.1.0-LITE
                    pix_chave_estatica: pix_chave ? pix_chave.value.trim() : "", // v2.8.5-LITE
                    mercadopago_test_mode: mp_test ? mp_test.value : "0" // v1.3.0-LITE
                })
            });

            const json = await resposta.json();
            if (!json.success) return alert(json.message || "Erro ao salvar empresa.");

            // Logo: gerenciada exclusivamente em "Minha Barbearia".
            alert("Configurações salvas com sucesso!");
            location.reload();

        } catch (e) {
            console.error(e);
            alert("Erro ao salvar configurações.");
        }
    });

    // --- LÓGICA DE BACKUP ---
    const btnBackup = document.getElementById("btnFazerBackup");
    if (btnBackup) {
        btnBackup.addEventListener("click", async () => {
            if (!confirm("Isso enviará uma cópia do banco de dados para a Master. Continuar?")) return;

            btnBackup.disabled = true;
            btnBackup.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ENVIANDO...';

            try {
                const res = await fetch("backup.php");
                const json = await res.json();

                if (json.success) {
                    alert("✅ Backup realizado com sucesso!");
                } else {
                    alert("❌ Erro no backup: " + json.message);
                }
            } catch (err) {
                alert("❌ Falha crítica na conexão de backup.");
            } finally {
                btnBackup.disabled = false;
                btnBackup.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> REALIZAR BACKUP AGORA';
                location.reload();
            }
        });
    }
});
