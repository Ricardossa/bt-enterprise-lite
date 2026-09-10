<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;

Auth::protegerPagina('ADMIN');

$tenantId = Auth::tenantId();

// v3.5.0: Busca Lista de Clientes com Estatísticas Operacionais
$clientes = Database::fetchAll("
    SELECT
        c.*,
        (SELECT COUNT(*) FROM senhas s WHERE s.cliente_id = c.id AND s.status = 'FINALIZADA') as total_visitas,
        (SELECT SUM(valor_total) FROM senhas s WHERE s.cliente_id = c.id AND s.pagamento_status = 'PAGO') as total_gasto,
        (SELECT MAX(created_at) FROM senhas s WHERE s.cliente_id = c.id) as ultima_visita,
        IFNULL(fs.saldo_pontos, 0) as pontos,
        (SELECT p.nome FROM clube_assinaturas a JOIN clube_planos p ON p.id = a.plano_id WHERE a.cliente_id = c.id AND a.status = 'ATIVA' AND a.data_fim >= CURDATE() LIMIT 1) as assinatura_plano
    FROM clientes c
    LEFT JOIN fidelidade_saldo fs ON fs.cliente_id = c.id AND fs.tenant_id = c.tenant_id
    WHERE c.tenant_id = ?
    ORDER BY c.nome ASC
", [$tenantId]);

$pageTitle = 'Meus Clientes & Fidelidade';
include __DIR__ . '/includes/header.php';
?>

<style>
    /* Fix para ícones quando o CDN falha ou demora */
    .bt-button i { min-width: 18px; text-align: center; margin-right: 8px; display: inline-block; vertical-align: middle; }
    .status-table button i { margin-right: 5px; }

    /* Estilo extra para garantir visibilidade dos botões de ação */
    .bt-button { display: inline-flex !important; align-items: center; justify-content: center; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Fix para Modais e visibilidade */
    .hidden { display: none !important; }

    .bt-modal {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85);
        display: flex; align-items: center; justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
    }

    .bt-modal-content {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 0;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        overflow: hidden;
    }

    .bt-modal-header {
        padding: 20px;
        background: rgba(255,255,255,0.03);
        border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
    }

    .bt-modal-body { padding: 25px; }
    .bt-modal-footer { padding: 20px; border-top: 1px solid var(--border); background: rgba(0,0,0,0.1); }

    .bt-close {
        background: transparent !important;
        border: none !important;
        color: #888 !important;
        width: auto !important;
        height: auto !important;
        min-width: 0 !important;
        min-height: 0 !important;
        flex: none !important; /* [VITAL] Impede o botão de esticar */
        cursor: pointer;
        font-size: 20px;
        padding: 5px !important;
        transition: color 0.2s ease;
        box-shadow: none !important;
    }
    .bt-close:hover {
        color: var(--danger) !important;
        background: transparent !important;
    }
</style>

<main class="bt-main">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <div>
            <h2><i class="fa-solid fa-users"></i> Meus Clientes</h2>
            <p style="color:var(--text2); font-size:14px;">Gestão de identidades, pontos de fidelidade e assinaturas.</p>
        </div>
        <div style="display:flex; gap:10px;">
             <button onclick="gerarQrFidelidade()" class="bt-button" style="background:var(--primary);">
                <i class="fa-solid fa-qrcode"></i> QR FIDELIDADE
            </button>
             <button onclick="abrirModalPlanosClube()" class="bt-button" style="background:var(--warning); color:#000;">
                <i class="fa-solid fa-crown"></i> CONFIGURAR CLUBES
            </button>
             <button onclick="abrirModalFidelidade()" class="bt-button bt-secondary">
                <i class="fa-solid fa-gift"></i> REGRAS PONTUAÇÃO
            </button>
             <button onclick="abrirModalSolicitacoesClube()" class="bt-button" style="background:var(--secondary); color:#000;">
                <i class="fa-solid fa-bell"></i> SOLICITAÇÕES <span id="badge-pendentes" class="badge badge-danger hidden">0</span>
            </button>
             <button onclick="abrirModalCliente()" class="bt-button bt-success">
                <i class="fa-solid fa-user-plus"></i> NOVO CLIENTE
            </button>
             <button onclick="alert('Funcionalidade de Exportação em breve!')" class="bt-button" style="background:#444;">
                <i class="fa-solid fa-file-export"></i> EXPORTAR
            </button>
        </div>
    </div>

    <div class="bt-card">
        <table class="status-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>WhatsApp</th>
                    <th style="text-align:center;">Visitas</th>
                    <th style="text-align:right;">Gasto Total</th>
                    <th style="text-align:center;">Pontos</th>
                    <th>Última Visita</th>
                    <th style="text-align:right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $c): ?>
                <tr>
                    <td>
                        <b style="color:#fff;"><?= htmlspecialchars($c['nome']) ?></b>
                        <?php if($c['status'] !== 'ATIVO'): ?>
                            <span class="badge badge-danger" style="font-size:9px;">BLOQUEADO</span>
                        <?php endif; ?>
                        <?php if(!empty($c['assinatura_plano'])): ?>
                            <span class="badge" style="background:var(--warning); color:#000; font-size:10px; font-weight:900; margin-left:5px;" title="Assinante: <?= htmlspecialchars($c['assinatura_plano']) ?>">
                                <i class="fa-solid fa-crown"></i> ASSINANTE
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="https://wa.me/55<?= $c['whatsapp'] ?>" target="_blank" style="color:var(--success); text-decoration:none;">
                            <i class="fa-brands fa-whatsapp"></i> <?= $c['whatsapp'] ?>
                        </a>
                    </td>
                    <td align="center"><b><?= $c['total_visitas'] ?></b></td>
                    <td align="right">R$ <?= number_format((float)$c['total_gasto'], 2, ',', '.') ?></td>
                    <td align="center">
                        <span class="badge" style="background:var(--secondary); color:#000; font-weight:900;">
                            <?= $c['pontos'] ?> PTS
                        </span>
                    </td>
                    <td>
                        <small style="color:var(--text3);">
                            <?= $c['ultima_visita'] ? date('d/m/Y', strtotime($c['ultima_visita'])) : '---' ?>
                        </small>
                    </td>
                    <td align="right">
                        <button onclick="abrirModalVendaClube(<?= $c['id'] ?>, '<?= addslashes($c['nome']) ?>')" class="bt-button" style="padding:5px 10px; font-size:11px; background:var(--warning); color:#000;">
                            <i class="fa-solid fa-crown"></i> ASSINAR
                        </button>
                        <button onclick="creditarPonto(<?= $c['id'] ?>, '<?= addslashes($c['nome']) ?>')" class="bt-button bt-secondary" style="padding:5px 10px; font-size:11px;">
                            <i class="fa-solid fa-plus"></i> PONTO
                        </button>
                        <button onclick='abrirModalCliente(<?= json_encode($c) ?>)' class="bt-button" style="padding:5px 10px; font-size:11px; background:var(--sidebar); border:1px solid var(--border);">
                            <i class="fa-solid fa-pen-to-square"></i> EDITAR
                        </button>
                        <button onclick="excluirCliente(<?= $c['id'] ?>, '<?= addslashes($c['nome']) ?>')" class="bt-button" style="padding:5px 10px; font-size:11px; background:rgba(255,77,77,0.1); border:1px solid var(--danger); color:var(--danger);">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($clientes)): ?>
                    <tr><td colspan="7" align="center" style="padding:50px; color:var(--text3);">Nenhum cliente cadastrado no módulo de fidelidade.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- MODAL: REGRAS DE PONTUAÇÃO -->
<div id="modalFidelidade" class="bt-modal hidden">
    <div class="bt-modal-content">
        <div class="bt-modal-header">
            <h3><i class="fa-solid fa-gift"></i> Configurar Fidelidade</h3>
            <button onclick="fecharModais()" class="bt-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="bt-modal-body">
            <div class="form-group">
                <label>Status do Módulo</label>
                <select id="fid_ativo" class="form-control">
                    <option value="1">Ativado</option>
                    <option value="0">Desativado</option>
                </select>
            </div>
            <div class="form-group" style="margin-top:20px;">
                <label>Meta de Pontos (Ex: 10)</label>
                <input type="number" id="fid_meta" class="form-control" placeholder="10">
                <small style="color:var(--text3); font-size:11px;">Quantos serviços o cliente deve pagar para ganhar o prêmio.</small>
            </div>
            <div class="form-group" style="margin-top:20px;">
                <label>Descrição do Prêmio</label>
                <input type="text" id="fid_premio" class="form-control" placeholder="Ex: 1 Corte Grátis">
            </div>
        </div>
        <div class="bt-modal-footer">
            <button onclick="salvarFidelidade()" class="bt-button bt-success" style="width:100%;">SALVAR REGRAS</button>
        </div>
    </div>
</div>

<!-- MODAL: CADASTRAR/EDITAR CLIENTE -->
<div id="modalCliente" class="bt-modal hidden">
    <div class="bt-modal-content">
        <div class="bt-modal-header">
            <h3 id="modalClienteTitle"><i class="fa-solid fa-user-plus"></i> Novo Cliente</h3>
            <button onclick="fecharModais()" class="bt-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="bt-modal-body">
            <input type="hidden" id="cli_id">
            <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" id="cli_nome" class="form-control" placeholder="Ex: João Silva">
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>WhatsApp</label>
                <input type="tel" id="cli_whatsapp" class="form-control" placeholder="71900000000">
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>E-mail (Opcional)</label>
                <input type="email" id="cli_email" class="form-control" placeholder="cliente@email.com">
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>Data de Nascimento (Opcional)</label>
                <input type="date" id="cli_nascimento" class="form-control">
            </div>
        </div>
        <div class="bt-modal-footer">
            <button onclick="salvarCliente()" id="btnSalvarCliente" class="bt-button bt-success" style="width:100%;">CADASTRAR CLIENTE</button>
        </div>
    </div>
</div>

<!-- MODAL: QR CODE FIDELIDADE -->
<div id="modalQrFidelidade" class="bt-modal hidden">
    <div class="bt-modal-content" style="text-align:center;">
        <div class="bt-modal-header">
            <h3><i class="fa-solid fa-qrcode"></i> QR Code de Fidelidade</h3>
            <button onclick="fecharModais()" class="bt-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="bt-modal-body">
            <p style="font-size:13px; color:var(--text2); margin-bottom:20px;">Exponha este código no balcão para o cliente escanear e se cadastrar.</p>
            <div id="fidelidade-qr-area" style="background:#fff; padding:20px; border-radius:15px; display:inline-block; margin-bottom:15px;"></div>
            <div style="background:rgba(0,0,0,0.3); padding:10px; border-radius:10px; font-size:11px; color:var(--secondary); font-family:monospace; margin-bottom:15px;">
                URL: <span id="fid-url-debug">--</span>
            </div>
            <button onclick="window.print()" class="bt-button" style="width:100%; background:var(--sidebar); border:1px solid var(--border);">
                <i class="fa-solid fa-print"></i> IMPRIMIR PARA O BALCÃO
            </button>
        </div>
    </div>
</div>

<!-- MODAL: GERENCIAR PLANOS DO CLUBE (v4.0 Diamond) -->
<div id="modalClubePlanos" class="bt-modal hidden">
    <div class="bt-modal-content" style="max-width: 600px;">
        <div class="bt-modal-header">
            <h3><i class="fa-solid fa-crown"></i> Planos de Assinatura</h3>
            <button onclick="fecharModais()" class="bt-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="bt-modal-body" id="bodyClubePlanos">
            <!-- Tabela de planos e formulário de novo plano -->
        </div>
        <div class="bt-modal-footer">
            <button onclick="salvarNovoPlanoClube()" class="bt-button bt-success" style="width:100%;">CADASTRAR NOVO PACOTE</button>
        </div>
    </div>
</div>

<!-- MODAL: VENDER ASSINATURA -->
<div id="modalVendaClube" class="bt-modal hidden">
    <div class="bt-modal-content">
        <div class="bt-modal-header">
            <h3>👑 Ativar Clube para <span id="venda_cliente_nome"></span></h3>
            <button onclick="fecharModais()" class="bt-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="bt-modal-body">
            <input type="hidden" id="venda_cliente_id">
            <div class="form-group">
                <label>Selecione o Plano</label>
                <select id="venda_plano_id" class="form-control">
                    <!-- Injetado via JS -->
                </select>
            </div>
            <p style="font-size:12px; color:var(--text2); margin-top:15px;">Ao confirmar, o cliente receberá os cortes previstos no plano imediatamente.</p>
        </div>
        <div class="bt-modal-footer">
            <button onclick="confirmarVendaClube()" class="bt-button bt-success" style="width:100%;">ATIVAR ASSINATURA AGORA</button>
        </div>
    </div>
</div>

<!-- MODAL: SOLICITAÇÕES PENDENTES (v4.5 Diamond) -->
<div id="modalClubeSolicitacoes" class="bt-modal hidden">
    <div class="bt-modal-content" style="max-width: 600px;">
        <div class="bt-modal-header">
            <h3><i class="fa-solid fa-bell"></i> Solicitações de Clube</h3>
            <button onclick="fecharModais()" class="bt-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="bt-modal-body" id="bodyClubeSolicitacoes">
            <!-- Injetado via JS -->
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
    // [v4.5.1] Motor de Notificação e Aprovação de Clube
    async function carregarNotificacoes() {
        try {
            const res = await fetch('api/v1/clube_gestao.php?action=listar_pendentes');
            const json = await res.json();
            const badge = document.getElementById('badge-pendentes');
            if (json.data && json.data.length > 0) {
                badge.innerText = json.data.length;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        } catch(e) {}
    }

    async function abrirModalSolicitacoesClube() {
        const body = document.getElementById('bodyClubeSolicitacoes');
        body.innerHTML = "Carregando solicitações...";
        document.getElementById('modalClubeSolicitacoes').classList.remove('hidden');

        try {
            const res = await fetch('api/v1/clube_gestao.php?action=listar_pendentes');
            const json = await res.json();

            if (json.data && json.data.length > 0) {
                body.innerHTML = json.data.map(a => `
                    <div style="background:var(--sidebar); border:1px solid var(--border); padding:20px; border-radius:15px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <b style="color:#fff; font-size:16px;">${a.cliente_nome}</b>
                            <p style="margin:5px 0; font-size:13px; color:var(--warning);">PLANO: ${a.plano_nome}</p>
                            <small style="color:var(--text3);">Zap: ${a.whatsapp}</small>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <button onclick="rejeitarSolicitacao(${a.id})" class="bt-button" style="background:rgba(255,0,0,0.1); border:1px solid var(--danger); color:var(--danger); padding:8px 12px;">✖</button>
                            <button onclick="aprovarSolicitacao(${a.id}, '${a.cliente_nome}')" class="bt-button bt-success" style="padding:8px 15px;">
                                <i class="fa-solid fa-check"></i> APROVAR
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                body.innerHTML = "<p style='text-align:center; padding:30px; color:var(--text3);'>Nenhuma solicitação pendente.</p>";
            }
        } catch(e) { body.innerHTML = "Erro ao carregar."; }
    }

    async function aprovarSolicitacao(id, nome) {
        if (!confirm(`Confirmar entrada de ${nome} no Clube de Vantagens?`)) return;
        try {
            const res = await fetch(`api/v1/clube_gestao.php?action=aprovar_assinatura&id=${id}`);
            const json = await res.json();
            if (json.success) {
                alert("Assinatura ativada com sucesso!");
                abrirModalSolicitacoesClube();
                carregarNotificacoes();
                location.reload(); // Recarrega para ver o selo VIP na lista
            }
        } catch(e) { alert("Erro ao aprovar."); }
    }

    async function rejeitarSolicitacao(id) {
        if (!confirm("Deseja rejeitar esta solicitação?")) return;
        try {
            await fetch(`api/v1/clube_gestao.php?action=remover_plano&is_assinatura=1&id=${id}`);
            abrirModalSolicitacoesClube();
            carregarNotificacoes();
        } catch(e) {}
    }

    // Chama no load
    carregarNotificacoes();
    // --- MÓDULO DE CLUBE DE ASSINATURA (SaaS) ---
    async function abrirModalPlanosClube() {
        document.getElementById('modalClubePlanos').classList.remove('hidden');
        renderizarPlanosClube();
    }

    async function renderizarPlanosClube() {
        const body = document.getElementById('bodyClubePlanos');
        body.innerHTML = "Carregando planos...";

        try {
            const res = await fetch('api/v1/clube_gestao.php?action=listar_planos');
            const json = await res.json();

            let html = `
                <table class="bt-table" width="100%">
                    <thead><tr><th>Nome</th><th>Preço</th><th>Cortes</th><th>Ações</th></tr></thead>
                    <tbody>`;

            if (json.data && json.data.length > 0) {
                html += json.data.map(p => `
                    <tr>
                        <td><b>${p.nome}</b></td>
                        <td>R$ ${p.preco}</td>
                        <td align="center">${p.qtd_cortes}</td>
                        <td align="right">
                            <button class='bt-button' style='padding:5px; background:var(--sidebar);' onclick='prepararEdicaoPlano(${JSON.stringify(p)})'>📝</button>
                            <button class='bt-button' style='padding:5px; background:rgba(255,0,0,0.1);' onclick='removerPlanoClube(${p.id})'>🗑️</button>
                        </td>
                    </tr>
                `).join('');
            } else {
                html += "<tr><td colspan='4' align='center'>Nenhum plano criado.</td></tr>";
            }

            html += `</tbody></table>
                    <hr style='margin:20px 0; opacity:0.1;'>
                    <h4 id="clube_form_title">Criar Novo Plano</h4>
                    <input type="hidden" id="clube_id">
                    <div style='display:grid; grid-template-columns: 2fr 1fr 1fr; gap:10px; margin-top:10px;'>
                        <input id='clube_nome' class='form-control' placeholder='Ex: Plano Mensal 4 Cortes'>
                        <input id='clube_preco' class='form-control' placeholder='150,00'>
                        <input id='clube_qtd' type='number' class='form-control' value='4'>
                    </div>`;

            body.innerHTML = html;
        } catch(e) { body.innerHTML = "Erro ao carregar."; }
    }

    function prepararEdicaoPlano(p) {
        document.getElementById('clube_id').value = p.id;
        document.getElementById('clube_nome').value = p.nome;
        document.getElementById('clube_preco').value = p.preco;
        document.getElementById('clube_qtd').value = p.qtd_cortes;
        document.getElementById('clube_form_title').innerText = "Editar Plano";
        document.getElementById('clube_nome').focus();
    }

    async function salvarNovoPlanoClube() {
        const id = document.getElementById('clube_id').value;
        const nome = document.getElementById('clube_nome').value;
        const preco = document.getElementById('clube_preco').value;
        const qtd = document.getElementById('clube_qtd').value;

        if (!nome || !preco) return alert("Preencha nome e preço.");

        try {
            const res = await fetch('api/v1/clube_gestao.php?action=salvar_plano', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, nome, preco, qtd })
            });
            const json = await res.json();
            if (json.success) {
                alert(json.message);
                renderizarPlanosClube();
            } else {
                alert(json.message);
            }
        } catch(e) { alert("Erro ao salvar."); }
    }

    async function removerPlanoClube(id) {
        if (!confirm("Remover este plano?")) return;
        try {
            await fetch(`api/v1/clube_gestao.php?action=remover_plano&id=${id}`, { method: 'POST' });
            renderizarPlanosClube();
        } catch(e) {}
    }

    async function abrirModalVendaClube(clienteId, clienteNome) {
        document.getElementById('venda_cliente_id').value = clienteId;
        document.getElementById('venda_cliente_nome').innerText = clienteNome;

        const res = await fetch('api/v1/clube_gestao.php?action=listar_planos');
        const json = await res.json();

        const select = document.getElementById('venda_plano_id');
        select.innerHTML = json.data.map(p => `<option value="${p.id}">${p.nome} - R$ ${p.preco}</option>`).join('');

        document.getElementById('modalVendaClube').classList.remove('hidden');
    }

    async function confirmarVendaClube() {
        const clienteId = document.getElementById('venda_cliente_id').value;
        const planoId = document.getElementById('venda_plano_id').value;

        try {
            const res = await fetch('api/v1/clube_gestao.php?action=vender_assinatura', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cliente_id: clienteId, plano_id: planoId })
            });
            const json = await res.json();
            alert(json.message);
            fecharModais();
        } catch(e) { alert("Erro ao ativar assinatura."); }
    }

    function gerarQrFidelidade() {
        const qrArea = document.getElementById('fidelidade-qr-area');
        const debugUrl = document.getElementById('fid-url-debug');

        // [v3.6.5] Lógica de URL Inteligente (Interna vs Externa)
        // Se estivermos acessando por um domínio (não IP), ou estivermos no domínio oficial, usa a URL pública
        const isDomain = !/^[0-9.]+$/.test(window.location.hostname);
        let baseUrl = window.location.origin;

        // Se houver uma URL pública configurada (vinda do PHP/Config), podemos injetar aqui
        // Por enquanto, usamos a inteligência de detecção de host
        const pathFidelidade = window.location.pathname.replace('clientes.php', 'fidelidade/');
        const currentUrl = baseUrl + pathFidelidade;

        debugUrl.innerText = currentUrl;

        try {
            const qr = qrcode(0, 'H');
            qr.addData(currentUrl);
            qr.make();
            qrArea.innerHTML = qr.createImgTag(8);
            document.getElementById('modalQrFidelidade').classList.remove('hidden');
        } catch (e) { alert("Erro ao gerar QR Code."); }
    }
    async function abrirModalFidelidade() {
        const res = await fetch('api/v1/fidelidade_config.php');
        const json = await res.json();
        if (json.success) {
            document.getElementById('fid_meta').value = json.data.meta_pontos;
            document.getElementById('fid_premio').value = json.data.premio_desc;
            document.getElementById('fid_ativo').value = json.data.ativo;
            document.getElementById('modalFidelidade').classList.remove('hidden');
        }
    }

    function abrirModalCliente(dados = null) {
        if (dados) {
            document.getElementById('modalClienteTitle').innerHTML = '<i class="fa-solid fa-user-pen"></i> Editar Cliente';
            document.getElementById('cli_id').value = dados.id;
            document.getElementById('cli_nome').value = dados.nome;
            document.getElementById('cli_whatsapp').value = dados.whatsapp;
            document.getElementById('cli_email').value = dados.email || '';
            document.getElementById('cli_nascimento').value = dados.data_nascimento || '';
            document.getElementById('btnSalvarCliente').innerText = 'ATUALIZAR CADASTRO';
        } else {
            document.getElementById('modalClienteTitle').innerHTML = '<i class="fa-solid fa-user-plus"></i> Novo Cliente';
            document.getElementById('cli_id').value = '';
            document.getElementById('cli_nome').value = '';
            document.getElementById('cli_whatsapp').value = '';
            document.getElementById('cli_email').value = '';
            document.getElementById('cli_nascimento').value = '';
            document.getElementById('btnSalvarCliente').innerText = 'CADASTRAR CLIENTE';
        }
        document.getElementById('modalCliente').classList.remove('hidden');
    }

    function fecharModais() {
        document.querySelectorAll('.bt-modal').forEach(m => m.classList.add('hidden'));
    }

    async function salvarFidelidade() {
        const meta = document.getElementById('fid_meta').value;
        const premio = document.getElementById('fid_premio').value;
        const ativo = document.getElementById('fid_ativo').value;

        const res = await fetch('api/v1/fidelidade_config.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ meta_pontos: meta, premio_desc: premio, ativo: ativo })
        });
        const json = await res.json();
        if (json.success) {
            alert(json.message);
            fecharModais();
        }
    }

    async function creditarPonto(id, nome) {
        if (!confirm(`Deseja adicionar 1 PONTO de fidelidade para ${nome}?`)) return;

        const res = await fetch('api/v1/cliente.php?action=creditar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cliente_id: id })
        });
        const json = await res.json();
        if (json.success) {
            alert(json.message);
            location.reload();
        } else {
            alert(json.message);
        }
    }

    async function salvarCliente() {
        const id = document.getElementById('cli_id').value;
        const nome = document.getElementById('cli_nome').value;
        const whatsapp = document.getElementById('cli_whatsapp').value;
        const email = document.getElementById('cli_email').value;
        const nascimento = document.getElementById('cli_nascimento').value;

        if(!nome || !whatsapp) return alert("Nome e WhatsApp são obrigatórios.");

        const metodo = id ? 'PUT' : 'POST';

        const res = await fetch('api/v1/cliente.php', {
            method: metodo,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nome, whatsapp, email, data_nascimento: nascimento })
        });
        const json = await res.json();
        if (json.success) {
            alert(json.message || "Sucesso!");
            location.reload();
        } else {
            alert(json.message);
        }
    }

    async function excluirCliente(id, nome) {
        if (!confirm(`TEM CERTEZA que deseja excluir permanentemente o cliente ${nome}?\nIsso removerá todo o histórico e pontos!`)) return;

        const res = await fetch('api/v1/cliente.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const json = await res.json();
        if (json.success) {
            alert(json.message);
            location.reload();
        } else {
            alert(json.message);
        }
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
