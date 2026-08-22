<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;

Auth::protegerPagina('ADMIN');

$pageTitle = 'Central de Conectividade';
include __DIR__ . '/includes/header.php';
?>

<style>
    .conn-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; margin-top: 25px; }
    .conn-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 30px; }
    .qr-preview-box { background: #fff; padding: 25px; border-radius: 20px; display: inline-block; box-shadow: 0 10px 30px rgba(0,0,0,0.3); margin-bottom: 20px; }
    .qr-preview-box img { width: 220px; height: 220px; }
</style>

<main class="bt-main">

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2><i class="fa-solid fa-globe"></i> Conectividade e Acesso</h2>
            <p style="color:var(--text2); font-size:14px;">Gerencie as URLs de acesso e gere as placas de QR Code para os clientes.</p>
        </div>
        <button id="btnSalvar" class="bt-button bt-success">
            <i class="fa-solid fa-floppy-disk"></i> Salvar Alterações
        </button>
    </div>

    <div class="conn-grid">

        <!-- CONFIGURAÇÕES DE URL -->
        <div style="display:flex; flex-direction:column; gap:25px;">
            <section class="conn-card">
                <h3 style="font-size:14px; color:var(--secondary); text-transform:uppercase; margin-bottom:20px;">🌐 Endereços do Sistema</h3>

                <div class="form-group">
                    <label>Modo de Operação</label>
                    <div style="display:flex; gap:20px; margin-top:10px;">
                        <label><input type="radio" name="modo" id="modo_lan" value="lan" checked> Rede Local (LAN)</label>
                        <label><input type="radio" name="modo" id="modo_cloud" value="cloud"> Nuvem (Cloud)</label>
                    </div>
                </div>

                <div class="form-group" style="margin-top:20px;">
                    <label>URL Interna (IP do Servidor)</label>
                    <input type="text" id="url_local" class="form-control" placeholder="http://192.168...">
                </div>

                <div class="form-group" style="margin-top:20px;">
                    <label>URL Pública (Domínio)</label>
                    <input type="text" id="url_publica" class="form-control" placeholder="https://fila.brandaotech.com.br">
                </div>

                <div class="form-group" style="margin-top:20px; border-top: 1px solid var(--border); padding-top: 15px;">
                    <label style="color:var(--secondary); font-size:12px;">🧠 ENDEREÇO DO CÉREBRO AI (Ollama)</label>
                    <input type="text" id="ai_url" class="form-control" placeholder="http://192.168.100.250:11434/api/generate">
                    <small style="color:var(--text2); font-size:10px;">Aponte para o seu Servidor Xeon (Local ou Público).</small>
                </div>

                <div class="form-group" style="margin-top:20px; border-top: 1px solid var(--border); padding-top: 15px;">
                    <label style="color:var(--secondary); font-size:12px;">🖨️ IP DA IMPRESSORA LOCAL (Para Nuvem)</label>
                    <div style="display:flex; gap:10px; margin-top:5px;">
                        <input type="text" id="local_print_ip" class="form-control" placeholder="Ex: 192.168.1.50" style="flex:1;">
                        <button id="btnTestarImpressora" type="button" class="bt-button" style="background:var(--sidebar); border:1px solid var(--border); font-size:11px; white-space:nowrap;">
                            <i class="fa-solid fa-plug"></i> TESTAR CONEXÃO
                        </button>
                    </div>
                    <div id="printStatusBox" style="margin-top:10px; padding:10px; border-radius:8px; display:none; font-size:12px; align-items:center; gap:10px;">
                        <i id="printStatusIcon" class="fa-solid"></i>
                        <span id="printStatusText"></span>
                    </div>
                    <p style="font-size:10px; color:var(--text2); margin-top:10px;">
                        Dica: O motor de impressão deve estar ativo no IP indicado (Porta 8001).
                    </p>
                </div>
            </section>

            <section class="conn-card">
                <h3 style="font-size:14px; color:var(--secondary); text-transform:uppercase; margin-bottom:20px;">⏰ Expediente (Segurança QR Code)</h3>
                <p style="color:var(--text2); font-size:12px; margin-bottom:15px;">Defina o horário em que a emissão de senhas pelo QR Code estará ativa.</p>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Abertura</label>
                        <input type="time" id="opening_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Fechamento</label>
                        <input type="time" id="closing_time" class="form-control">
                    </div>
                </div>
                <small style="color:var(--text2); display:block; margin-top:10px;">Fora deste horário, o link do QR Code (impresso ou digital) será bloqueado.</small>
            </section>

            <section class="conn-card">
                <h3 style="font-size:14px; color:var(--secondary); text-transform:uppercase; margin-bottom:20px;">📱 Social e Slogan</h3>
                <div class="form-group">
                    <label>Slogan da Empresa</label>
                    <input type="text" id="slogan" class="form-control" placeholder="O que aparece na base do totem e TV">
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px;">
                    <div class="form-group">
                        <label>WhatsApp</label>
                        <input type="text" id="whatsapp" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Instagram</label>
                        <input type="text" id="instagram" class="form-control">
                    </div>
                </div>
            </section>
        </div>

        <!-- QR CODE E PLACA -->
        <section class="conn-card" style="text-align:center;">
            <h3 style="font-size:14px; color:var(--secondary); text-transform:uppercase; margin-bottom:25px;">📲 Placa de Atendimento</h3>

            <div class="qr-preview-box" id="qrContainer">
                <!-- QR Gerado aqui -->
            </div>

            <p id="qrDestino" style="font-size:12px; color:var(--text2); margin-bottom:30px; font-family:monospace;"></p>

            <div style="display:flex; flex-direction:column; gap:12px;">
                <button id="btnGerarPlaca" class="bt-button bt-primary" style="padding:20px; font-size:18px; border-radius:15px;">
                    <i class="fa-solid fa-print"></i> GERAR PLACA DE IMPRESSÃO
                </button>
                <div style="display:flex; gap:10px;">
                    <button id="btnBaixarQR" class="bt-button" style="flex:1; background:var(--sidebar); border:1px solid var(--border);">
                        <i class="fa-solid fa-download"></i> Baixar PNG
                    </button>
                </div>
            </div>

            <p style="margin-top:25px; font-size:11px; color:var(--text2); opacity:0.6;">
                Dica: O QR Code deve apontar para o link que o cliente usará no celular.
            </p>
        </section>

    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script src="assets/js/api.js?v=5"></script>
<script src="assets/js/conectividade.js?v=5"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
