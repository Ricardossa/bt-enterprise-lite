<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;

Auth::protegerPagina('ADMIN');

$pageTitle = 'Administração do Sistema';
include __DIR__ . '/includes/header.php';
?>

<style>
    .config-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; margin-top: 25px; }
    .config-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 30px; }
    .config-section-title { font-size: 14px; font-weight: 800; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }

    .module-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .module-card { background: var(--sidebar); border: 1px solid var(--border); border-radius: 15px; padding: 20px; transition: .2s; text-decoration: none; color: inherit; }
    .module-card:hover { border-color: var(--secondary); transform: translateY(-3px); background: rgba(29, 180, 255, 0.05); }
    .module-card i { font-size: 24px; color: var(--secondary); margin-bottom: 10px; }
    .module-card h4 { margin: 0; font-size: 16px; }
    .module-card p { font-size: 12px; color: var(--text2); margin-top: 5px; }

    .branding-box { text-align: center; padding: 30px; background: var(--sidebar); border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 20px; }
    .branding-logo-preview { max-height: 80px; margin-bottom: 20px; filter: drop-shadow(0 0 10px rgba(0,0,0,0.5)); }
</style>

<main class="bt-main">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <div>
            <h2><i class="fa-solid fa-gears"></i> Central de Controle</h2>
            <p style="color:var(--text2); font-size:14px;">Configurações vitais e gerenciamento de módulos Enterprise.</p>
        </div>
        <button id="btnSalvar" class="bt-button bt-success" style="padding: 12px 25px;">
            <i class="fa-regular fa-floppy-disk"></i> SALVAR TUDO
        </button>
    </div>

    <div class="config-grid">

        <!-- COLUNA 1: CONFIGURAÇÕES CORE -->
        <div style="display:flex; flex-direction:column; gap:25px;">

            <section class="config-card">
                <div class="config-section-title"><i class="fa-solid fa-building"></i> Identidade da Empresa</div>

                <div class="form-group">
                    <label>Nome Comercial</label>
                    <input type="text" id="empresa" class="form-control" placeholder="Ex: Farmácia Brandão">
                </div>

                <div class="form-group" style="margin-top:20px;">
                    <label>Rótulo do Atendimento (Ex: Paciente, Fornecedor)</label>
                    <input type="text" id="label_cliente" class="form-control" placeholder="Paciente">
                    <small style="color:var(--text2); font-size:11px;">Este nome será usado no painel do operador e relatórios.</small>
                </div>

                <div class="form-group" style="margin-top:20px;">
                    <label>Logotipo Oficial (PNG/JPG)</label>
                    <div class="branding-box">
                        <img id="empresaLogoPreview" src="assets/img/logo-placeholder.png" class="branding-logo-preview" onerror="this.src='uploads/logo.png'">
                        <input type="file" id="logo" class="form-control" accept=".png,.jpg,.jpeg">
                <small class="text-muted" style="display:block; margin-top:5px; font-size:10px;">
                    <b>Recomendado:</b> 540 × 360 px (3:2), fundo transparente. Formatos: PNG ou WebP.
                </small>
                        <small style="color:var(--text2); display:block; mt:10px;">Recomendado: 500x500px fundo transparente.</small>
                    </div>
                </div>
            </section>

            <section class="config-card">
                <div class="config-section-title"><i class="fa-brands fa-whatsapp"></i> Notificações WhatsApp</div>
                <p style="color:var(--text2); font-size:13px; margin-bottom:20px;">Integre sua API (Evolution, Z-API, etc) para enviar confirmações automáticas.</p>

                <div class="form-group" style="margin-bottom:15px;">
                    <label>Habilitar Notificações?</label>
                    <select id="whatsapp_enabled" class="form-control">
                        <option value="0">Não</option>
                        <option value="1">Sim</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:15px;">
                    <label>URL da Instância / Gateway</label>
                    <input type="text" id="whatsapp_api_url" class="form-control" placeholder="https://api.meuzap.com/message/sendText/instancia">
                </div>

                <div class="form-group">
                    <label>Token de Acesso (apikey)</label>
                    <input type="password" id="whatsapp_api_token" class="form-control" placeholder="Seu token de segurança">
                </div>
            </section>

            <section class="config-card">
                <div class="config-section-title"><i class="fa-solid fa-scale-balanced"></i> Regras do Totem & Prioridade</div>
                <p style="color:var(--text2); font-size:13px; margin-bottom:20px;">Configure como o Totem (Físico e Mobile) deve tratar as prioridades.</p>

                <div class="form-group" style="margin-bottom:15px; background:rgba(29, 180, 255, 0.05); padding:15px; border-radius:10px;">
                    <label>Habilitar Triagem de Prioridade?</label>
                    <select id="feature_priority_selection" class="form-control">
                        <option value="1">Sim (Mostra tela Normal/Prioritário)</option>
                        <option value="0">Não (Emite Normal instantaneamente)</option>
                    </select>
                    <small style="color:var(--text2); font-size:10px;">Se desativado, o sistema ignora a tela de escolha para maior rapidez.</small>
                </div>

                <div class="form-group" style="margin-bottom:15px;">
                    <label>Modo de Chamada</label>
                    <select id="priority_mode" class="form-control">
                        <option value="STRICT">Prioridade Total (Sempre primeiro)</option>
                        <option value="BALANCED">Intercalado (Regra de Equilíbrio)</option>
                    </select>
                </div>

                <div id="priority_ratio_container" class="form-group">
                    <label>Proporção (Prioridades : 1 Normal)</label>
                    <input type="number" id="priority_ratio" class="form-control" placeholder="3" min="1" max="10">
                    <small style="color:var(--text2); font-size:11px;">Ex: Se colocar 3, o sistema chamará 3 prioritários e depois 1 normal.</small>
                </div>
            </section>

            <section class="config-card" style="border-left: 4px solid #32BCAD;">
                <div class="config-section-title"><i class="fa-solid fa-money-bill-transfer"></i> Pagamentos & Checkout SaaS</div>
                <p style="color:var(--text2); font-size:13px; margin-bottom:20px;">Configure como os agendamentos online devem ser cobrados.</p>

                <div class="form-group" style="margin-bottom:20px; background:rgba(29, 180, 255, 0.05); padding:15px; border-radius:12px;">
                    <label style="color:var(--secondary); font-weight:bold;">Estratégia de Recebimento</label>
                    <select id="payment_strategy" class="form-control">
                        <option value="mercadopago">Mercado Pago (Automático + Taxas)</option>
                        <option value="manual">PIX Direto (Chave Fixa + Sem Taxas)</option>
                        <option value="disabled">Desativado (Não cobrar no agendamento)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label>Liberação do Agendamento</label>
                    <select id="booking_release_mode" class="form-control">
                        <option value="immediate">Imediata (Reserva primeiro, paga depois)</option>
                        <option value="after_payment">Após Pagamento (Só confirma se houver PIX)</option>
                    </select>
                    <small style="color:var(--text3); font-size:10px;">No modo 'Após Pagamento', o cliente precisa notificar o PIX para o horário ser dele.</small>
                </div>

                <hr style="margin:20px 0; border-color:var(--border);">

                <!-- MODO MERCADO PAGO -->
                <div id="box-mercadopago" style="background:rgba(0,150,255,0.05); padding:20px; border-radius:15px; border:1px solid rgba(0,150,255,0.1); margin-bottom:20px;">
                    <label style="color:var(--secondary); font-weight:bold;"><i class="fa-solid fa-bolt"></i> Mercado Pago (Automático)</label>
                    <div class="form-group" style="margin-top:10px;">
                        <label>Access Token (Produção)</label>
                        <input type="password" id="mercadopago_token" class="form-control" placeholder="APP_USR-...">
                        <small style="color:var(--text2); font-size:10px;">Gera QR Code dinâmico e confirma o pagamento sozinho.</small>
                    </div>
                </div>

                <!-- MODO PIX DIRETO -->
                <div style="background:rgba(255,255,255,0.02); padding:20px; border-radius:15px; border:1px solid var(--border);">
                    <label style="color:#fff; font-weight:bold;"><i class="fa-solid fa-key"></i> PIX Direto (Chave Fixa)</label>
                    <div class="form-group" style="margin-top:10px;">
                        <label>Sua Chave PIX (E-mail, CPF, Celular)</label>
                        <input type="text" id="pix_chave_estatica" class="form-control" placeholder="suachave@email.com">
                        <small style="color:var(--text2); font-size:10px;">Caso o Mercado Pago esteja desligado, usaremos esta chave fixa.</small>
                    </div>
                </div>

                <div class="form-group" style="margin-top:20px; background:rgba(0,0,0,0.2); padding:15px; border-radius:10px;">
                    <label style="color:var(--warning);"><i class="fa-solid fa-vial"></i> Modo de Teste / Simulação</label>
                    <select id="mercadopago_test_mode" class="form-control">
                        <option value="0">Desativado (Usa API Real)</option>
                        <option value="1">Ativado (Simula PIX sem cobrança)</option>
                    </select>
                </div>
            </section>

            <section class="config-card">
                <div class="config-section-title"><i class="fa-solid fa-network-wired"></i> Conectividade e Live</div>
                <p style="color:var(--text2); font-size:13px; margin-bottom:20px;">Configure como o sistema se comunica com os celulares e a Master.</p>

                <a href="conectividade.php" class="bt-button" style="width:100%; text-decoration:none; text-align:center; background:var(--sidebar); border:1px solid var(--border);">
                    <i class="fa-solid fa-globe"></i> Acessar Central de Conectividade
                </a>
            </section>

            <section class="config-card" style="border-left: 4px solid var(--success);">
                <div class="config-section-title"><i class="fa-solid fa-shield-halved"></i> Brandão Tech SafeBackup</div>
                <p style="color:var(--text2); font-size:13px; margin-bottom:15px;">Proteja os dados do cliente enviando cópias diárias para a nuvem.</p>

                <div id="backup-status" style="padding:10px; background:rgba(0,0,0,0.2); border-radius:10px; font-size:12px; margin-bottom:15px;">
                    Estado: <span style="color:var(--success);">Ativo</span> | Último: <strong>Nunca realizado</strong>
                </div>

                <button id="btnFazerBackup" class="bt-button bt-primary" style="width:100%;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> REALIZAR BACKUP AGORA
                </button>
            </section>

        </div>

        <!-- COLUNA 2: ATALHOS DE MÓDULOS -->
        <div>
            <section class="config-card">
                <div class="config-section-title"><i class="fa-solid fa-cubes"></i> Gestão de Módulos</div>

                <div class="module-grid">
                    <a href="servicos.php" class="module-card">
                        <i class="fa-solid fa-user-doctor"></i>
                        <h4>Serviços</h4>
                        <p>Filas e especialidades.</p>
                    </a>
                    <a href="guiches.php" class="module-card">
                        <i class="fa-solid fa-desktop"></i>
                        <h4>Guichês</h4>
                        <p>Mesas e locais físicos.</p>
                    </a>
                    <a href="promocoes.php" class="module-card">
                        <i class="fa-solid fa-tags"></i>
                        <h4>Promoções</h4>
                        <p>Ofertas no celular.</p>
                    </a>
                    <a href="operadores.php" class="module-card">
                        <i class="fa-solid fa-user-tie"></i>
                        <h4>Operadores</h4>
                        <p>Equipe e acessos.</p>
                    </a>
                </div>
            </section>

            <div class="bt-card" style="margin-top:25px; border-left: 4px solid var(--warning); background: rgba(255, 193, 7, 0.05);">
                <h4 style="color:var(--warning); margin-bottom:10px;"><i class="fa-solid fa-circle-info"></i> Atenção</h4>
                <p style="font-size:12px; color:var(--text2); line-height:1.4;">
                    Alterações na identidade da empresa podem levar alguns minutos para serem sincronizadas com a TV e os dispositivos móveis.
                </p>
            </div>
        </div>

    </div>

</main>

<script src="assets/js/api.js?v=4"></script>
<script src="assets/js/config.js?v=4"></script>
<script src="assets/js/configuracoes.js?v=4"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
