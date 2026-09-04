/**
 * BT Queue Enterprise - Central de Conectividade e QR Code Premium
 */

document.addEventListener("DOMContentLoaded", () => {
    const campos = {
        url_local: document.getElementById("url_local"),
        url_publica: document.getElementById("url_publica"),
        whatsapp: document.getElementById("whatsapp"),
        instagram: document.getElementById("instagram"),
        slogan: document.getElementById("slogan"),
        modo_lan: document.getElementById("modo_lan"),
        modo_cloud: document.getElementById("modo_cloud"),
        local_print_ip: document.getElementById("local_print_ip"),
        ai_url: document.getElementById("ai_url"),
        opening_time: document.getElementById("opening_time"),
        closing_time: document.getElementById("closing_time")
    };

    const btnSalvar = document.getElementById("btnSalvar");
    const btnBaixarQR = document.getElementById("btnBaixarQR");
    const btnGerarPlaca = document.getElementById("btnGerarPlaca");
    const btnTestarImpressora = document.getElementById("btnTestarImpressora");

    async function testarImpressora() {
        const ip = campos.local_print_ip.value.trim() || window.location.hostname;
        const statusBox = document.getElementById("printStatusBox");
        const statusIcon = document.getElementById("printStatusIcon");
        const statusText = document.getElementById("printStatusText");

        statusBox.style.display = "flex";
        statusBox.style.background = "rgba(255,255,255,0.05)";
        statusText.innerText = "Conectando em " + ip + ":8001...";
        statusIcon.className = "fa-solid fa-spinner fa-spin";

        try {
            // Tenta um fetch simples para ver se a porta responde (CORS mode)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000);

            const res = await fetch(`http://${ip}:8001/`, {
                method: 'GET',
                mode: 'no-cors',
                signal: controller.signal
            });

            clearTimeout(timeoutId);
            statusBox.style.background = "rgba(24, 201, 100, 0.1)";
            statusBox.style.color = "#18C964";
            statusText.innerText = "CONECTADO! O motor de impressão está ativo.";
            statusIcon.className = "fa-solid fa-circle-check";

        } catch (e) {
            statusBox.style.background = "rgba(255, 77, 77, 0.1)";
            statusBox.style.color = "#FF4D4D";
            statusText.innerText = "OFFLINE. Verifique se o Ligar_Impressora_Local.bat está aberto.";
            statusIcon.className = "fa-solid fa-circle-xmark";
        }
    }

    async function carregarConfiguracoes() {
        console.log("📂 Carregando configurações...");
        try {
            const res = await fetch("api/configuracoes.php");
            const json = await res.json();
            if (!json.success) {
                console.error("❌ Falha na API:", json.message);
                return;
            }

            const cfg = json.data;
            console.log("✅ Dados recebidos:", cfg);

            if (cfg.url_local) campos.url_local.value = cfg.url_local;
            if (cfg.url_publica) campos.url_publica.value = cfg.url_publica;
            if (cfg.whatsapp) campos.whatsapp.value = cfg.whatsapp;
            if (cfg.instagram) campos.instagram.value = cfg.instagram;
            if (cfg.slogan) campos.slogan.value = cfg.slogan;
            if (cfg.local_print_ip) campos.local_print_ip.value = cfg.local_print_ip;
            if (cfg.ai_url) campos.ai_url.value = cfg.ai_url;
            if (cfg.opening_time) campos.opening_time.value = cfg.opening_time;
            if (cfg.closing_time) campos.closing_time.value = cfg.closing_time;

            if (cfg.modo === "cloud") campos.modo_cloud.checked = true;
            else campos.modo_lan.checked = true;

            atualizarQRCode();
        } catch (e) {
            console.error("❌ Erro ao carregar configurações:", e);
        }
    }

    async function salvarConfiguracoes() {
        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Salvando...';

        const dados = {
            modo: campos.modo_cloud.checked ? "cloud" : "lan",
            url_local: campos.url_local.value.trim(),
            url_publica: campos.url_publica.value.trim(),
            whatsapp: campos.whatsapp.value.trim(),
            instagram: campos.instagram.value.trim(),
            slogan: campos.slogan.value.trim(),
            local_print_ip: campos.local_print_ip.value.trim(),
            ai_url: campos.ai_url.value.trim(),
            opening_time: campos.opening_time.value,
            closing_time: campos.closing_time.value
        };

        try {
            const res = await fetch("api/configuracoes.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(dados)
            });
            const json = await res.json();
            if (json.success) {
                alert("Configurações salvas com sucesso!");
                atualizarQRCode();
            }
        } catch (e) { alert("Erro ao salvar configurações."); }

        btnSalvar.disabled = false;
        btnSalvar.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Salvar Alterações';
    }

    function atualizarQRCode() {
        let base = campos.modo_cloud.checked
            ? campos.url_publica.value.trim()
            : campos.url_local.value.trim();

        if(!base) {
            document.getElementById("qrDestino").textContent = "Configure uma URL.";
            return;
        }

        // --- DETECÇÃO INTELIGENTE DE AMBIENTE ---
        let pathMobile = "/live_premium/index.php";

        // Se a URL atual tiver o padrão de diretórios da Master, mantemos o prefixo
        if (window.location.pathname.includes("/painel_v4/public/")) {
            pathMobile = "/painel_v4/public/live_premium/index.php";
        }

        const destino = base.replace(/\/$/, "") + pathMobile;

        const qrContainer = document.getElementById("qrContainer");
        const qrDestino = document.getElementById("qrDestino");

        qrContainer.innerHTML = "";
        qrDestino.textContent = "Link do QR Code: " + destino;

        const qr = qrcode(0, "M");
        qr.addData(destino);
        qr.make();
        qrContainer.innerHTML = qr.createImgTag(8, 0);
    }

    // GERAÇÃO DE PLACA DE IMPRESSÃO PROFISSIONAL
    function gerarPlaca() {
        const qrImg = document.querySelector("#qrContainer img");
        if (!qrImg) return alert("Configure a URL primeiro.");

        const empresa = document.getElementById("empresaNome")?.innerText || "Sua Empresa";
        const slogan = campos.slogan.value || "Tecnologia e Inovação";

        const w = window.open("", "_blank");
        w.document.write(`
            <html>
            <head>
                <title>Placa de Atendimento - ${empresa}</title>
                <style>
                    body { font-family: 'Segoe UI', sans-serif; text-align: center; padding: 50px; background: #fff; }
                    .placa { border: 10px solid #0019FF; border-radius: 40px; padding: 60px 40px; max-width: 600px; margin: 0 auto; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
                    .logo-bt { height: 40px; margin-bottom: 30px; opacity: 0.3; }
                    h1 { font-size: 42px; margin: 0; color: #0019FF; font-weight: 900; }
                    p { font-size: 20px; color: #666; margin: 15px 0 40px; }
                    .qr-box { background: #fff; padding: 20px; display: inline-block; border: 2px solid #eee; border-radius: 20px; }
                    .qr-box img { width: 350px; height: 350px; }
                    .footer-text { margin-top: 50px; font-size: 18px; font-weight: bold; color: #0019FF; }
                    @media print { .btn-print { display: none; } }
                    .btn-print { background: #0019FF; color: #fff; padding: 15px 40px; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; margin-top: 30px; }
                </style>
            </head>
            <body>
                <div class="placa">
                    <img src="https://api.brandaotech.com.br/uploads/logo/logo.png" class="logo-bt">
                    <h1>RETIRE SUA SENHA</h1>
                    <p>Escaneie o código abaixo para escolher o serviço e retirar sua senha diretamente no seu celular.</p>
                    <div class="qr-box"><img src="${qrImg.src}"></div>
                    <div class="footer-text">${slogan}</div>
                    <button class="btn-print" onclick="window.print()">🖨️ IMPRIMIR PLACA</button>
                </div>
            </body>
            </html>
        `);
        w.document.close();
    }

    if (btnSalvar) btnSalvar.addEventListener("click", salvarConfiguracoes);
    if (btnGerarPlaca) btnGerarPlaca.addEventListener("click", gerarPlaca);
    if (btnTestarImpressora) btnTestarImpressora.addEventListener("click", testarImpressora);

    if (btnBaixarQR) {
        btnBaixarQR.addEventListener("click", () => {
            const img = document.querySelector("#qrContainer img");
            if (!img) return;
            const a = document.createElement("a");
            a.href = img.src;
            a.download = "qrcode-atendimento.png";
            a.click();
        });
    }

    [campos.url_local, campos.url_publica, campos.modo_lan, campos.modo_cloud].forEach(c => {
        if (c) c.addEventListener("change", atualizarQRCode);
    });

    carregarConfiguracoes();
});
