<?php
declare(strict_types=1);

use BTQueue\Core\Auth;
use BTQueue\Core\DashboardController;
use BTQueue\Core\ActivityService;

error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    require_once __DIR__ . '/../bootstrap.php';

    Auth::iniciar();
    Auth::protegerPagina('ADMIN');

    $operador = Auth::operador();
    $dashboard = new DashboardController();
    $dashboard->ensureActivityIsAlive();

    $stats = $dashboard->getStats();
    $health = $dashboard->getHealth();
    $activities = ActivityService::getRecent(12);

} catch (Throwable $e) {
    die("<h1>ERRO NO DASHBOARD: " . $e->getMessage() . "</h1><pre>" . $e->getTraceAsString() . "</pre>");
}

$pageTitle = 'Console Operacional (NOC)';
include __DIR__ . '/includes/header.php';
?>

<style>
    .noc-card { background: var(--card); border-radius: 12px; padding: 20px; border: 1px solid var(--border); box-shadow: var(--shadow); height: 100%; }
    .noc-metric-card { text-align: center; }
    .noc-metric-card .stat-number { font-size: 48px; font-weight: bold; color: var(--secondary); margin: 15px 0; line-height: 1; }
    .noc-metric-card h3 { font-size: 14px; color: var(--text2); text-transform: uppercase; letter-spacing: 1px; }

    .noc-health-bar { display: flex; gap: 20px; background: var(--sidebar); padding: 12px 25px; border-radius: 50px; border: 1px solid var(--border); margin-bottom: 30px; }
    .noc-health-item { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
    .noc-health-dot { width: 8px; height: 8px; border-radius: 50%; box-shadow: 0 0 5px currentColor; }

    .noc-timeline { display: flex; flex-direction: column; gap: 15px; }
    .noc-item { display: flex; gap: 15px; align-items: flex-start; }
    .noc-time { font-size: 11px; color: var(--text2); min-width: 45px; padding-top: 3px; }
    .noc-msg { font-size: 13px; flex: 1; }
    .noc-user { font-size: 10px; background: var(--primary); color: #fff; padding: 1px 6px; border-radius: 4px; margin-left: 5px; }

    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
</style>

<main class="bt-main">
    <!-- BARRA DE ATUALIZAÇÃO OTA -->
    <div id="update-bar" style="display:none; background: linear-gradient(90deg, var(--primary), var(--secondary)); padding: 15px 25px; border-radius: 12px; margin-bottom: 25px; align-items: center; justify-content: space-between; color: #fff; box-shadow: 0 4px 15px rgba(29, 180, 255, 0.3);">
        <div style="display:flex; align-items:center; gap:15px;">
            <i class="fa-solid fa-rocket animate__animated animate__bounceIn" style="font-size: 24px;"></i>
            <div>
                <b style="font-size:16px;">Nova versão disponível: <span id="update-version">--</span></b>
                <p style="font-size:12px; opacity:0.9; margin:0;">Melhorias e correções de segurança prontas para instalar.</p>
            </div>
        </div>
        <button id="btn-apply-update" class="bt-button" style="background:#fff; color:var(--primary); font-weight:bold; padding:10px 25px;">
            ATUALIZAR AGORA
        </button>
    </div>

    <div class="noc-health-bar">
        <?php foreach ($health as $h): ?>
        <div class="noc-health-item" style="color: <?= $h['color'] ?>;">
            <div class="noc-health-dot" style="background: <?= $h['color'] ?>;"></div>
            <?= $h['nome'] ?>: <?= $h['status'] ?>
        </div>
        <?php endforeach; ?>

        <!-- MONITOR DE LICENÇA -->
        <div style="flex:1; display:flex; justify-content:flex-end; gap:20px; align-items:center;">
             <div id="license-status-badge" style="font-size:11px; font-weight:bold; padding:4px 12px; border-radius:4px; background:rgba(255,255,255,0.05);">
                LICENÇA: <span id="val-license-status">--</span>
             </div>
             <div style="font-size:10px; color:var(--text2); display:flex; align-items:center; gap:5px;">
                <i class="fa-solid fa-microchip" style="color:var(--secondary);"></i> KERNEL ATIVO
             </div>
             <button id="btn-sync-now" class="bt-button" style="padding:4px 12px; font-size:10px; background:var(--sidebar); border:1px solid var(--border);">
                <i class="fa-solid fa-sync"></i> FORÇAR PULSO
             </button>
        </div>
    </div>

    <div class="grid-4">
        <div class="noc-card noc-metric-card">
            <h3>Senhas Emitidas</h3>
            <div class="stat-number" id="val-emitidas"><?= $stats['emitidas'] ?></div>
            <p style="font-size:12px; color:var(--text2);">Total do Dia</p>
        </div>
        <div class="noc-card noc-metric-card">
            <h3>Atendimentos</h3>
            <div class="stat-number" id="val-chamadas"><?= $stats['chamadas'] ?></div>
            <p style="font-size:12px; color:var(--text2);">Em curso / Finalizados</p>
        </div>
        <div class="noc-card noc-metric-card">
            <h3>Na Fila</h3>
            <div class="stat-number" id="val-pendentes" style="color:var(--warning);"><?= $stats['pendentes'] ?></div>
            <p style="font-size:12px; color:var(--text2);">Aguardando Chamada</p>
        </div>
        <div class="noc-card noc-metric-card">
            <h3>Finalizadas</h3>
            <div class="stat-number" id="val-finalizadas"><?= $stats['finalizadas'] ?></div>
            <p style="font-size:12px; color:var(--text2);">Atendimentos Concluídos</p>
        </div>
    </div>

    <div class="bt-grid" style="grid-template-columns: 1.5fr 1fr;">
        <section class="noc-card">
            <h2 style="font-size:18px; margin-bottom:20px; color:var(--secondary);">📈 Atividade em Tempo Real</h2>
            <div class="noc-timeline" id="timeline">
                <?php foreach ($activities as $act): ?>
                <div class="noc-item">
                    <div class="noc-time">
                        <?php
                            $timeStr = $act['data_criacao'] ?? $act['created_at'] ?? null;
                            echo $timeStr ? date('H:i:s', strtotime($timeStr)) : '--:--:--';
                        ?>
                    </div>
                    <div class="noc-msg">
                        <?php
                            $icon = $act['tipo'] === 'SUCCESS' ? '🟢' : ($act['tipo'] === 'WARNING' ? '🟡' : ($act['tipo'] === 'CRITICAL' ? '🔴' : '🔵'));
                            echo $icon . ' ' . htmlspecialchars($act['mensagem']);
                            if ($act['usuario']) echo "<span class='noc-user'>{$act['usuario']}</span>";
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="noc-card">
            <h2 style="font-size:18px; margin-bottom:20px;">⚡ Ações de Gestão</h2>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <a href="financeiro.php" class="bt-button bt-primary" style="text-decoration:none; text-align:center; background:var(--success); border-color:var(--success);">
                    <i class="fa-solid fa-money-bill-trend-up"></i> Ver Financeiro Hoje
                </a>
                <a href="barber_operador.php" target="_blank" class="bt-button bt-primary" style="text-decoration:none; text-align:center;">
                    <i class="fa-solid fa-scissors"></i> Painel do Barbeiro
                </a>
                <a href="operadores.php" class="bt-button" style="text-decoration:none; text-align:center; background:var(--sidebar); border:1px solid var(--border);">
                    <i class="fa-solid fa-user-tie"></i> Gerenciar Equipe
                </a>
                <a href="totem.php" class="bt-button" style="text-decoration:none; text-align:center; background:var(--sidebar); border:1px solid var(--border);">
                    <i class="fa-solid fa-tablet-screen-button"></i> Abrir Totem
                </a>
                <a href="tv_v2.php" class="bt-button" style="text-decoration:none; text-align:center; background:var(--sidebar); border:1px solid var(--border);">
                    <i class="fa-solid fa-tv"></i> Abrir TV Pública
                </a>
            </div>
        </aside>
    </div>
</main>

<script>
    async function refreshDashboard() {
        try {
            const response = await fetch('api/dashboard_stats.php');
            const json = await response.json();
            if (!json.success) return;

            document.getElementById('val-emitidas').innerText = json.stats.emitidas;
            document.getElementById('val-chamadas').innerText = json.stats.chamadas;
            document.getElementById('val-pendentes').innerText = json.stats.pendentes;
            document.getElementById('val-finalizadas').innerText = json.stats.finalizadas;

            // Atualiza Status da Licença
            if (json.license) {
                const valStatus = document.getElementById('val-license-status');
                const status = json.license.status.toUpperCase();
                valStatus.innerText = status;
                valStatus.style.color = (status === 'ATIVA') ? '#18C964' : '#FF4D4D';
            }

            const timeline = document.getElementById('timeline');
            timeline.innerHTML = json.activities.map(act => {
                const time = act.data_criacao.split(' ')[1];
                const icon = act.tipo === 'SUCCESS' ? '🟢' : (act.tipo === 'WARNING' ? '🟡' : (act.tipo === 'CRITICAL' ? '🔴' : '🔵'));
                const user = act.usuario ? `<span class="noc-user">${act.usuario}</span>` : '';
                return `<div class="noc-item"><div class="noc-time">${time}</div><div class="noc-msg">${icon} ${act.mensagem}${user}</div></div>`;
            }).join('');
        } catch (e) { console.error(e); }
    }

    // --- LÓGICA DE ATUALIZAÇÃO OTA ---
    let pendingUpdate = null;

    async function checkUpdates() {
        try {
            const res = await fetch('api/update.php');
            const json = await res.json();
            if (json.success && json.data.update_available) {
                pendingUpdate = json.data;
                document.getElementById('update-version').innerText = 'v' + pendingUpdate.version;
                document.getElementById('update-bar').style.display = 'flex';
            }
        } catch (e) { console.error("Update check failed", e); }
    }

    document.getElementById('btn-apply-update').onclick = async () => {
        if (!pendingUpdate) return;

        const btn = document.getElementById('btn-apply-update');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> INSTALANDO...';

        try {
            const res = await fetch('api/update.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    release_id: pendingUpdate.release_id
                })
            });
            const json = await res.json();
            if (json.success) {
                alert("Sistema atualizado com sucesso! A página será reiniciada.");
                window.location.reload();
            } else {
                alert("Erro na atualização: " + json.message);
                btn.disabled = false;
                btn.innerText = 'TENTAR NOVAMENTE';
            }
        } catch (e) {
            alert("Falha crítica durante a atualização.");
            btn.disabled = false;
        }
    };

    // --- LÓGICA DE SINCRONIA MANUAL ---
    document.getElementById('btn-sync-now').onclick = async () => {
        const btn = document.getElementById('btn-sync-now');
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-sync fa-spin"></i> SINCRONIZANDO...';

        try {
            const res = await fetch('pulse.php');
            const json = await res.json();
            if (json.success) {
                alert("Sincronização concluída com sucesso!");
                refreshDashboard();
            } else {
                alert("Master não respondeu: " + (json.message || 'Erro desconhecido'));
            }
        } catch (e) { alert("Erro de conexão ao sincronizar."); }

        btn.disabled = false;
        btn.innerHTML = oldHtml;
    };

    checkUpdates();
    setInterval(refreshDashboard, 3000);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
