<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;

Auth::protegerPagina('ADMIN');

$pageTitle = 'Gestão de Operadores';
include __DIR__ . '/includes/header.php';
?>

<style>
    .op-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 25px; }
    .op-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; transition: .2s; }
    .op-card:hover { transform: translateY(-5px); border-color: var(--secondary); }
    .op-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
    .op-avatar { width: 45px; height: 45px; background: var(--sidebar); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--secondary); border: 1px solid var(--border); }
    .op-info { flex: 1; }
    .op-name { font-weight: bold; font-size: 16px; margin: 0; }
    .op-login { font-size: 12px; color: var(--text2); }
    .op-badges { display: flex; flex-direction: column; gap: 8px; }
    .op-badge { font-size: 11px; padding: 4px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 6px; background: var(--sidebar); color: var(--text2); }
    .op-actions { display: flex; gap: 10px; margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border); }
    .op-btn { flex: 1; padding: 8px; border: none; border-radius: 6px; font-size: 12px; font-weight: bold; cursor: pointer; transition: .2s; }
    .op-btn-edit { background: rgba(29, 180, 255, 0.1); color: var(--secondary); border: 1px solid rgba(29, 180, 255, 0.2); }
    .op-btn-delete { background: rgba(255, 77, 77, 0.1); color: var(--danger); border: 1px solid rgba(255, 77, 77, 0.2); }
</style>

<main class="bt-main">

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2><i class="fa-solid fa-user-tie"></i> Equipe de Profissionais</h2>
            <p style="color:var(--text2); font-size:14px;">Gerencie os barbeiros, suas especialidades e cadeiras de atendimento.</p>
        </div>
        <button id="btnNovo" class="bt-button bt-primary">
            <i class="fa-solid fa-plus"></i> Novo Profissional
        </button>
    </div>

    <div id="listaOperadores" class="op-grid">
        <!-- Injetado via JS -->
        <p style="color:var(--text2);">Carregando operadores...</p>
    </div>

</main>

<script src="assets/js/api.js?v=1"></script>
<script src="assets/js/config.js?v=1"></script>
<script src="assets/js/modal.js?v=1"></script>
<script src="assets/js/operadores.js?v=2"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
