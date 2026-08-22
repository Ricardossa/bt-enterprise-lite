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
        IFNULL(fs.saldo_pontos, 0) as pontos
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
        background: rgba(255,255,255,0.1);
        border: none; color: #fff;
        width: 35px; height: 35px;
        border-radius: 50%; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; transition: 0.2s;
    }
    .bt-close:hover { background: var(--danger); }
</style>

<main class="bt-main">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <div>
            <h2><i class="fa-solid fa-users"></i> Meus Clientes</h2>
            <p style="color:var(--text2); font-size:14px;">Gestão de identidades e pontos de fidelidade.</p>
        </div>
        <div style="display:flex; gap:10px;">
             <button onclick="gerarQrFidelidade()" class="bt-button" style="background:var(--primary);">
                <i class="fa-solid fa-qrcode"></i> QR FIDELIDADE
            </button>
             <button onclick="abrirModalFidelidade()" class="bt-button bt-secondary">
                <i class="fa-solid fa-gift"></i> REGRAS DE FIDELIDADE
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
                        <button onclick="creditarPonto(<?= $c['id'] ?>, '<?= addslashes($c['nome']) ?>')" class="bt-button bt-secondary" style="padding:5px 10px; font-size:11px;">
                            <i class="fa-solid fa-plus"></i> PONTO
                        </button>
                        <button class="bt-button" style="padding:5px 10px; font-size:11px;">
                            <i class="fa-solid fa-eye"></i> PERFIL
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

<!-- MODAL: REGRAS DE FIDELIDADE -->
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

<!-- MODAL: CADASTRAR CLIENTE -->
<div id="modalCliente" class="bt-modal hidden">
    <div class="bt-modal-content">
        <div class="bt-modal-header">
            <h3><i class="fa-solid fa-user-plus"></i> Novo Cliente</h3>
            <button onclick="fecharModais()" class="bt-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="bt-modal-body">
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
            <button onclick="salvarCliente()" class="bt-button bt-success" style="width:100%;">CADASTRAR CLIENTE</button>
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

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
    function gerarQrFidelidade() {
        const qrArea = document.getElementById('fidelidade-qr-area');
        const debugUrl = document.getElementById('fid-url-debug');

        // v3.5.6: Detecta a URL de fidelidade baseada na barbearia atual
        const currentUrl = window.location.origin + window.location.pathname.replace('clientes.php', 'fidelidade/');
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

    function abrirModalCliente() {
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
        const nome = document.getElementById('cli_nome').value;
        const whatsapp = document.getElementById('cli_whatsapp').value;
        const email = document.getElementById('cli_email').value;
        const nascimento = document.getElementById('cli_nascimento').value;

        if(!nome || !whatsapp) return alert("Nome e WhatsApp são obrigatórios.");

        const res = await fetch('api/v1/cliente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome, whatsapp, email, data_nascimento: nascimento })
        });
        const json = await res.json();
        if (json.success) {
            alert("Cliente cadastrado com sucesso!");
            location.reload();
        } else {
            alert(json.message);
        }
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
