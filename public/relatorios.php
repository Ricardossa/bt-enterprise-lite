<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;

Auth::protegerPagina('ADMIN');

$pageTitle = 'Relatórios de Performance';
include __DIR__ . '/includes/header.php';
?>

<style>
    .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 25px; }
    .metric-card { background: var(--card); border: 1px solid var(--border); border-radius: 15px; padding: 25px; }
    .metric-title { font-size: 14px; font-weight: 800; color: var(--secondary); text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

    .stats-table { width: 100%; border-collapse: collapse; }
    .stats-table th { text-align: left; font-size: 11px; color: var(--text2); text-transform: uppercase; padding: 10px; border-bottom: 1px solid var(--border); }
    .stats-table td { padding: 12px 10px; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.03); }

    .big-number { font-size: 32px; font-weight: bold; color: #fff; }
    .unit { font-size: 14px; color: var(--text2); font-weight: normal; margin-left: 5px; }
</style>

<main class="bt-main">

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2><i class="fa-solid fa-chart-pie"></i> Inteligência de Negócio</h2>
            <p style="color:var(--text2); font-size:14px;">Análise de produtividade e tempo médio de espera. <span id="label-periodo" style="color:var(--secondary); font-weight:bold; margin-left:10px;"></span></p>
        </div>

        <div style="text-align:right; display:flex; flex-direction:column; gap:10px;">
            <div style="font-size:11px; color:var(--text3); text-transform:uppercase;">
                <i class="fa-solid fa-arrows-rotate"></i> Auto-refresh: <b id="timer-refresh">60s</b> |
                Última: <b id="last-update">--:--</b>
            </div>
            <!-- FILTROS DE PERÍODO -->
            <div style="display:flex; gap:10px; background:var(--sidebar); padding:5px; border-radius:10px; border:1px solid var(--border);">
                <button onclick="setPeriodo('hoje')" class="bt-button" id="btn-hoje" style="padding:8px 15px; font-size:12px;">HOJE</button>
                <button onclick="setPeriodo('mes')" class="bt-button" id="btn-mes" style="padding:8px 15px; font-size:12px; background:transparent;">MÊS</button>
                <button onclick="setPeriodo('ano')" class="bt-button" id="btn-ano" style="padding:8px 15px; font-size:12px; background:transparent;">ANO</button>
            </div>
        </div>
    </div>

    <!-- RESUMO RÁPIDO -->
    <div class="report-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-top: 30px;">
        <div class="metric-card">
            <div class="metric-title"><i class="fa-solid fa-ticket"></i> Total Emitidas</div>
            <div class="big-number" id="total-emitidas">--</div>
            <div style="margin-top:10px; font-size:12px; color:var(--text2); display:flex; flex-direction:column; gap:5px;">
                <div style="display:flex; justify-content:space-between;">
                    <span>📍 Presencial: <b id="total-presencial" style="color:#fff">--</b></span>
                    <span>📅 Agenda: <b id="total-agendados" style="color:var(--warning)">--</b></span>
                </div>
                <div style="display:flex; justify-content:space-between; border-top: 1px solid rgba(255,255,255,0.05); padding-top:5px;">
                    <span>♿ Prioritárias: <b id="total-prioritarias" style="color:var(--warning)">--</b></span>
                    <span>👤 Normais: <b id="total-normais" style="color:#fff">--</b></span>
                </div>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-title"><i class="fa-solid fa-clock-rotate-left"></i> Espera Média</div>
            <div class="big-number" id="espera-global">-- <span class="unit">min</span></div>
        </div>
        <div class="metric-card">
            <div class="metric-title"><i class="fa-solid fa-bolt"></i> Pico de Movimento</div>
            <div class="big-number" id="horario-pico">--</div>
        </div>
    </div>

    <div class="report-grid">
        <!-- RANKING OPERADORES -->
        <section class="metric-card">
            <div class="metric-title"><i class="fa-solid fa-medal"></i> Ranking de Produtividade</div>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Atendente</th>
                        <th>Total</th>
                        <th>Tempo Médio (Atend.)</th>
                    </tr>
                </thead>
                <tbody id="lista-ranking">
                    <!-- Injetado via JS -->
                </tbody>
            </table>
        </section>

        <!-- SITUAÇÃO DA FILA ATUAL -->
        <section class="metric-card" style="border-top: 4px solid var(--primary);">
            <div class="metric-title"><i class="fa-solid fa-users-viewfinder"></i> Quadro de Fila Atual</div>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th style="text-align:center;">Total</th>
                        <th style="text-align:center;">♿</th>
                        <th style="text-align:center;">👤</th>
                    </tr>
                </thead>
                <tbody id="lista-fila-atual">
                    <!-- Injetado via JS -->
                </tbody>
            </table>
        </section>
    </div>

    <div class="report-grid">
        <!-- ESPERA POR SERVIÇO -->
        <section class="metric-card">
            <div class="metric-title"><i class="fa-solid fa-hourglass-half"></i> Espera por Serviço</div>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th>Tempo de Espera</th>
                    </tr>
                </thead>
                <tbody id="lista-espera-servico">
                    <!-- Injetado via JS -->
                </tbody>
            </table>
        </section>
    </div>

    <!-- 🧠 BT DIAMOND AI INSIGHTS (v6.7) -->
    <section class="metric-card" style="margin-top: 25px; border-left: 4px solid var(--secondary); background: rgba(29, 180, 255, 0.02);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <div class="metric-title" style="margin-bottom:0;"><i class="fa-solid fa-brain"></i> Análise de Inteligência Artificial</div>
            <button id="btnSolicitarAI" class="bt-button bt-primary" style="font-size:11px; padding:8px 15px;">
                <i class="fa-solid fa-wand-magic-sparkles"></i> SOLICITAR INSIGHT AGORA
            </button>
        </div>

        <div id="ai-response-box" class="hidden animate__animated animate__fadeIn">
            <div style="background: var(--sidebar); border: 1px solid var(--border); border-radius: 12px; padding: 20px; color: #fff; line-height: 1.6; font-size: 14px; white-space: pre-wrap;">
                <div id="ai-content"></div>
                <div style="margin-top:15px; font-size:10px; color:var(--text3); border-top:1px solid rgba(255,255,255,0.05); padding-top:10px; text-align:right;">
                    <i class="fa-solid fa-microchip"></i> Processado localmente pelo cérebro Xeon Diamond.
                </div>
            </div>
        </div>

        <div id="ai-loading" class="hidden" style="padding: 40px; text-align: center;">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 30px; color: var(--secondary);"></i>
            <p style="color:var(--text2); margin-top:15px;">O cérebro da unidade está analisando seus dados de hoje...</p>
        </div>
    </section>

</main>

<script>
let filtroAtual = 'hoje';
let refreshTimer = 60;

function getDatas() {
    const agora = new Date();
    let inicio, fim;

    const format = (d) => {
        const z = (n) => (n < 10 ? '0' : '') + n;
        return `${d.getFullYear()}-${z(d.getMonth() + 1)}-${z(d.getDate())}`;
    };

    if (filtroAtual === 'mes') {
        inicio = format(new Date(agora.getFullYear(), agora.getMonth(), 1));
        fim = format(new Date(agora.getFullYear(), agora.getMonth() + 1, 0));
    } else if (filtroAtual === 'ano') {
        inicio = format(new Date(agora.getFullYear(), 0, 1));
        fim = format(new Date(agora.getFullYear(), 11, 31));
    } else {
        inicio = format(agora);
        fim = format(agora);
    }
    return { inicio, fim };
}

function setPeriodo(p) {
    filtroAtual = p;
    ['hoje', 'mes', 'ano'].forEach(btn => {
        const el = document.getElementById('btn-' + btn);
        if (el) el.style.background = (btn === p) ? 'var(--primary)' : 'transparent';
    });
    refreshTimer = 60; // Reinicia o timer ao trocar filtro
    carregarDados();
}

async function carregarDados() {
    const { inicio, fim } = getDatas();
    document.getElementById('label-periodo').innerText = (inicio === fim) ? `(${inicio.split('-').reverse().join('/')})` : `(${inicio.split('-').reverse().join('/')} até ${fim.split('-').reverse().join('/')})`;

    try {
        const res = await fetch(`api/relatorios_stats.php?inicio=${inicio}&fim=${fim}`);
        if (!res.ok) {
            if (res.status === 401) { window.location = 'login.php'; return; }
            throw new Error(`Erro HTTP: ${res.status}`);
        }

        const json = await res.json();
        if (!json.success) { console.error("API Error:", json.message); return; }
        const d = json.data;

        // Resumo
        document.getElementById('total-emitidas').innerText = d.resumo.total_emitidas || 0;
        document.getElementById('total-presencial').innerText = d.resumo.total_presencial || 0;
        document.getElementById('total-agendados').innerText = d.resumo.total_agendados || 0;
        document.getElementById('total-prioritarias').innerText = d.resumo.total_prioritarias || 0;
        document.getElementById('total-normais').innerText = d.resumo.total_normais || 0;
        document.getElementById('espera-global').innerHTML = `${Math.round(d.resumo.espera_global || 0)} <span class="unit">min</span>`;

        // Horário de Pico
        if (d.picos && d.picos.length > 0) {
            const pico = [...d.picos].sort((a,b) => b.total - a.total)[0];
            document.getElementById('horario-pico').innerText = pico.hora;
        }

        // Ranking Operadores
        const rankingBody = document.getElementById('lista-ranking');
        rankingBody.innerHTML = d.ranking.map(op => `
            <tr>
                <td><b>${op.nome}</b></td>
                <td>${op.total} senhas</td>
                <td>${Math.round(op.tempo_medio_atendimento || 0)} min</td>
            </tr>
        `).join('') || '<tr><td colspan="3" class="text-center">Nenhum dado.</td></tr>';

        // Situação Fila Atual
        const filaBody = document.getElementById('lista-fila-atual');
        filaBody.innerHTML = d.fila_atual.map(f => `
            <tr>
                <td><b>${f.nome}</b></td>
                <td align="center"><span class="badge" style="background:var(--primary)">${f.aguardando}</span></td>
                <td align="center" style="color:var(--warning)"><b>${f.prioritarias}</b></td>
                <td align="center">${f.normais}</td>
            </tr>
        `).join('') || '<tr><td colspan="4" class="text-center">Ninguém aguardando.</td></tr>';

        // Espera por Serviço
        const esperaBody = document.getElementById('lista-espera-servico');
        esperaBody.innerHTML = d.espera_servico.map(s => `
            <tr>
                <td><b>${s.nome}</b></td>
                <td style="color: ${s.tempo_espera > 15 ? 'var(--danger)' : 'var(--success)'}; font-weight: bold;">
                    ${Math.round(s.tempo_espera || 0)} min
                </td>
            </tr>
        `).join('') || '<tr><td colspan="2" class="text-center">Nenhum dado.</td></tr>';

        // Update timestamp
        document.getElementById('last-update').innerText = new Date().toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});

    } catch (e) { console.error(e); }
}

// Timer de Auto-Refresh
setInterval(() => {
    if (refreshTimer > 0) {
        refreshTimer--;
        const el = document.getElementById('timer-refresh');
        if (el) el.innerText = refreshTimer + 's';
    } else {
        refreshTimer = 60;
        carregarDados();
    }
}, 1000);

document.getElementById('btnSolicitarAI').onclick = async () => {
    const btn = document.getElementById('btnSolicitarAI');
    const loading = document.getElementById('ai-loading');
    const box = document.getElementById('ai-response-box');
    const content = document.getElementById('ai-content');

    btn.disabled = true;
    loading.classList.remove('hidden');
    box.classList.add('hidden');

    try {
        const res = await fetch('api/ai_insight.php');
        const json = await res.json();

        if (json.success) {
            content.innerText = json.analise;
            box.classList.remove('hidden');
        } else {
            alert(json.message || "O cérebro está ocupado no momento.");
        }
    } catch (e) {
        alert("Falha na comunicação com o motor de inteligência.");
    } finally {
        loading.classList.add('hidden');
        btn.disabled = false;
    }
};

document.addEventListener('DOMContentLoaded', carregarDados);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
