<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;

Auth::iniciar();
if (!Auth::autenticado()) {
    header('Location: login.php');
    exit;
}

if (Auth::isAdmin()) {
    // v2.3.6: Bloqueio estrito para Admin entrar em painel de atendimento
    header('Location: dashboard.php');
    exit;
}

$operadorLogado = Auth::operador();
$isAdmin = ($operadorLogado['nivel'] === 'ADMIN');
$guiches  = Database::fetchAll("SELECT id, nome FROM guiches WHERE ativo = 1 ORDER BY nome ASC");

// [LITE v2.7.6] Recursos sempre liberados no Lite
$hasAgenda = true;

if ($isAdmin && empty($operadorLogado['guiche_id'])) {
    // Se for ADMIN e não estiver em um guichê, redireciona para o Dashboard (O controle da plataforma)
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Atendimento Profissional';
include __DIR__ . '/includes/header.php';
?>

<style>
    .op-focus-card { text-align: center; padding: 60px 40px; background: radial-gradient(circle at center, var(--card) 0%, var(--sidebar) 100%); border: 2px solid var(--border); }
    .op-senha-focus { font-size: 160px; font-weight: 900; color: var(--secondary); text-shadow: 0 0 30px rgba(29, 180, 255, 0.3); line-height: 1; margin: 20px 0; }
    .op-status-info { background: var(--sidebar); padding: 15px 25px; border-radius: 10px; border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .op-next-item { background: var(--sidebar); border: 1px solid var(--border); border-radius: 8px; padding: 15px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
    .op-action-bar { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 25px; }
    .op-btn-huge { height: 80px; font-size: 20px; display: flex; align-items: center; justify-content: center; gap: 12px; }
</style>

<main class="bt-main">

    <div class="op-status-info">
        <div>
            <span style="color:var(--text2); font-size:12px;">👤 ATENDENTE:</span>
            <strong style="margin-left:8px;"><?= htmlspecialchars($operadorLogado['nome']) ?></strong>
        </div>
        <div style="text-align:right;">
            <span style="color:var(--text2); font-size:12px;">📍 LOCAL:</span>
            <strong id="guicheAtual" style="margin-left:8px; color:var(--secondary);">--</strong>
        </div>
    </div>

    <div class="bt-grid" style="grid-template-columns: 1.8fr 1fr;">
        <div style="display:flex; flex-direction:column; gap:25px;">
            <section class="noc-card op-focus-card">
                <h3 style="font-size:14px; color:var(--text2); text-transform:uppercase; letter-spacing:2px; margin:0;">Senha em Atendimento</h3>
                <div id="senhaAtual" class="op-senha-focus animate__animated">--</div>
                <div style="color:var(--success); font-weight:bold; letter-spacing:1px;" id="statusAtendimento">LIVRE PARA CHAMADA</div>
            </section>

            <section class="noc-card">
                <div style="display:grid; grid-template-columns: 1fr; gap:20px; margin-bottom:25px;">
                    <div class="form-group">
                        <label>Cadeira / Estação Ativa</label>
                        <select id="guiche" class="form-control">
                            <option value="">Selecione sua cadeira...</option>
                            <?php foreach ($guiches as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="op-action-bar">
                    <button id="btnChamar" class="bt-button bt-primary op-btn-huge"><i class="fa-solid fa-play"></i> CHAMAR</button>
                    <button id="btnRechamar" class="bt-button bt-warning op-btn-huge" disabled><i class="fa-solid fa-bullhorn"></i> RECHAMAR</button>
                    <button id="btnFinalizar" class="bt-button bt-success op-btn-huge" disabled><i class="fa-solid fa-check-double"></i> FINALIZAR</button>
                </div>
            </section>
        </div>

        <section class="noc-card">
            <?php if ($hasAgenda): ?>
            <h2 style="font-size:16px; margin-bottom:20px; color:var(--warning); text-transform:uppercase;">📅 Agenda do Dia</h2>
            <div id="agenda" class="op-next-list">
                <p style="color: var(--text3); font-size: 13px; text-align: center; padding: 20px;">Carregando agenda...</p>
            </div>

            <hr style="margin: 20px 0; border-color: var(--border);">
            <?php endif; ?>

            <h2 style="font-size:16px; margin-bottom:20px; color:var(--text2); text-transform:uppercase;">📋 Próximos na Fila</h2>
            <div id="fila" class="op-next-list"></div>

            <!-- NOVO: HISTÓRICO DE CHAMADAS (v2.7.6) -->
            <hr style="margin: 20px 0; border-color: var(--border);">
            <h2 style="font-size:13px; margin-bottom:15px; color:var(--text3); text-transform:uppercase;"><i class="fa-solid fa-history"></i> Chamadas Recentes</h2>
            <div id="historico" class="op-next-list" style="opacity:0.6;">
                <p style="color: var(--text3); font-size: 11px; text-align: center; padding: 10px;">Sem chamadas recentes.</p>
            </div>
        </section>
    </div>

</main>

<script>
    window.BT_USER_NIVEL = '<?= $operadorLogado['nivel'] ?>';
</script>
<script src="assets/js/api.js?v=4.7"></script>
<script src="assets/js/toast.js?v=4.7"></script>
<script src="app_v2.js?v=5.8.0"></script>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const operador = <?php echo json_encode($operadorLogado, JSON_UNESCAPED_UNICODE); ?>;
    const guiche  = document.getElementById('guiche');
    if(guiche && operador.guiche_id){
        guiche.value = operador.guiche_id;
        guiche.setAttribute('disabled','disabled'); // [CADEIRA FIXA]
    }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
