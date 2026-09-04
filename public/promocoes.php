<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;

Auth::protegerPagina('ADMIN');

$pageTitle = 'Promoções';
include __DIR__ . '/includes/header.php';
?>

<main class="bt-main">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>🎁 Gerenciar Promoções</h2>
        <div style="display:flex; gap:10px;">
            <button id="btnConfigVisual" class="bt-button" style="background:#666; font-size:13px;">
                🎨 Identidade Mobile
            </button>
            <button id="btnNovo" class="bt-button bt-primary">
                ➕ Nova Promoção
            </button>
        </div>
    </div>

    <div class="bt-card">
        <table class="status-table">
            <thead>
                <tr>
                    <th>Ordem</th>
                    <th>Título</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="listaPromocoes">
                <!-- Conteúdo carregado via JS -->
            </tbody>
        </table>
    </div>
</main>

<script src="assets/js/api.js?v=4.6"></script>
<script src="assets/js/config.js?v=4.6"></script>
<script src="assets/js/modal.js?v=4.6"></script>
<script src="assets/js/promocoes.js?v=4.6"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
