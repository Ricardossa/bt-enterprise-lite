<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;

Auth::protegerPagina('ADMIN');

$pageTitle = 'Gestão Financeira';
include __DIR__ . '/includes/header.php';
?>

<style>
    .fin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 25px; }
    .fin-card { background: var(--card); border: 1px solid var(--border); border-radius: 15px; padding: 25px; }
    .fin-title { font-size: 13px; font-weight: 800; color: var(--text2); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; }
    .fin-value { font-size: 32px; font-weight: 900; color: #fff; }
    .fin-value small { font-size: 14px; font-weight: normal; color: var(--text3); }

    .stats-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .stats-table th { text-align: left; font-size: 11px; color: var(--text3); text-transform: uppercase; padding: 12px 10px; border-bottom: 1px solid var(--border); }
    .stats-table td { padding: 15px 10px; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.03); }

    .barber-avatar { width: 35px; height: 35px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; }
</style>

<main class="bt-main">

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2><i class="fa-solid fa-money-bill-trend-up"></i> Fluxo de Caixa & Performance</h2>
            <p style="color:var(--text2); font-size:14px;">Acompanhamento financeiro individual e por serviço. <span id="label-periodo" style="color:var(--secondary); font-weight:bold;"></span></p>
        </div>

        <div style="display:flex; gap:10px; background:var(--sidebar); padding:5px; border-radius:12px; border:1px solid var(--border);">
            <button onclick="setPeriodo('hoje')" class="bt-button" id="btn-hoje" style="padding:8px 15px; font-size:12px;">HOJE</button>
            <button onclick="setPeriodo('semana')" class="bt-button" id="btn-semana" style="padding:8px 15px; font-size:12px; background:transparent;">ESTA SEMANA</button>
            <button onclick="setPeriodo('mes')" class="bt-button" id="btn-mes" style="padding:8px 15px; font-size:12px; background:transparent;">MÊS</button>
            <input type="date" id="filtro-inicio" style="width:130px; font-size:10px; margin-top:0; height:32px;" onchange="setPeriodo('custom')">
            <input type="date" id="filtro-fim" style="width:130px; font-size:10px; margin-top:0; height:32px;" onchange="setPeriodo('custom')">
        </div>
    </div>

    <!-- RESUMO RÁPIDO -->
    <div class="fin-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="fin-card" style="border-left: 4px solid var(--primary);">
            <div class="fin-title">Receita Bruta</div>
            <div class="fin-value" id="val-bruta" style="font-size:24px;">R$ 0,00</div>
        </div>
        <div class="fin-card" style="border-left: 4px solid var(--secondary);">
            <div class="fin-title">Comissões (A Pagar)</div>
            <div class="fin-value" id="val-comissao" style="color:var(--secondary); font-size:24px;">R$ 0,00</div>
        </div>
        <div class="fin-card" style="border-left: 4px solid var(--success);">
            <div class="fin-title">Total Recebido</div>
            <div class="fin-value" id="val-paga" style="color:var(--success); font-size:24px;">R$ 0,00</div>
        </div>
        <div class="fin-card" style="border-left: 4px solid var(--warning);">
            <div class="fin-title">Assinaturas VIP</div>
            <div class="fin-value" id="val-vip" style="color:var(--warning); font-size:24px;">0</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:25px; margin-top:25px;">

        <!-- PERFORMANCE POR BARBEIRO -->
        <section class="fin-card">
            <h3 style="font-size:16px; margin-bottom:20px;"><i class="fa-solid fa-scissors"></i> Performance por Profissional</h3>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Barbeiro</th>
                        <th style="text-align:center;">Serviços</th>
                        <th style="text-align:center; color:var(--warning);">VIP/Club</th>
                        <th style="text-align:right;">Bruto</th>
                        <th style="text-align:right; color:var(--secondary);">Comissão</th>
                        <th style="text-align:right;">Ações</th>
                    </tr>
                </thead>
                <tbody id="lista-barbeiros">
                    <!-- Injetado via JS -->
                </tbody>
            </table>
        </section>

        <!-- SERVIÇOS MAIS RENTÁVEIS -->
        <section class="fin-card">
            <h3 style="font-size:16px; margin-bottom:20px;"><i class="fa-solid fa-tags"></i> Top Serviços/Combos</h3>
            <div id="lista-servicos">
                <!-- Injetado via JS -->
            </div>
        </section>

    </div>

    <!-- 🕵️‍♂️ AUDITORIA DE CAIXA -->
    <div class="fin-card" style="margin-top:25px; border-top: 4px solid var(--warning);">
        <h3 style="font-size:16px; margin-bottom:20px;"><i class="fa-solid fa-magnifying-glass-dollar"></i> Conferência de Recebimentos (Serviços sem Baixa)</h3>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Finalizado em</th>
                    <th>Barbeiro</th>
                    <th>Cliente / Serviço</th>
                    <th style="text-align:right;">Valor</th>
                    <th style="text-align:right;">Ações</th>
                </tr>
            </thead>
            <tbody id="lista-pendencias">
                <!-- Injetado via JS -->
            </tbody>
        </table>
    </div>

    <!-- 💰 HISTÓRICO DE PAGAMENTOS -->
    <div class="fin-card" style="margin-top:25px; border-top: 4px solid var(--primary);">
        <h3 style="font-size:16px; margin-bottom:20px;"><i class="fa-solid fa-clock-rotate-left"></i> Histórico de Pagamentos aos Barbeiros</h3>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Data Pagamento</th>
                    <th>Barbeiro</th>
                    <th>Período Apurado</th>
                    <th style="text-align:right;">Valor Pago</th>
                    <th style="text-align:right;">Responsável</th>
                </tr>
            </thead>
            <tbody id="lista-historico">
                <!-- Injetado via JS -->
            </tbody>
        </table>
    </div>

</main>

<script>
let filtroAtual = 'hoje';

function formatMoeda(v) {
    return parseFloat(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function getDatas() {
    const hoje = new Date();
    let inicio, fim;
    const format = (d) => {
        const year = d.getFullYear();
        const month = (d.getMonth() + 1).toString().padStart(2, '0');
        const day = d.getDate().toString().padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (filtroAtual === 'semana') {
        const agora = new Date();
        const dia = agora.getDay();
        const diff = agora.getDate() - dia + (dia === 0 ? -6 : 1);
        const segunda = new Date(agora.setDate(diff));
        inicio = format(segunda);
        fim = format(hoje);
    } else if (filtroAtual === 'mes') {
        inicio = format(new Date(hoje.getFullYear(), hoje.getMonth(), 1));
        fim = format(new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0));
    } else if (filtroAtual === 'custom') {
        inicio = document.getElementById('filtro-inicio').value;
        fim = document.getElementById('filtro-fim').value;
        if (!inicio || !fim) {
            inicio = format(hoje);
            fim = format(hoje);
        }
    } else {
        inicio = format(hoje);
        fim = format(hoje);
    }
    return { inicio, fim };
}

function setPeriodo(p) {
    filtroAtual = p;
    ['hoje', 'semana', 'mes'].forEach(btn => {
        const el = document.getElementById('btn-' + btn);
        if (el) el.style.background = (btn === p) ? 'var(--primary)' : 'transparent';
    });
    carregarDados();
}

async function carregarDados() {
    const { inicio, fim } = getDatas();
    document.getElementById('label-periodo').innerText = `(${inicio.split('-').reverse().join('/')} até ${fim.split('-').reverse().join('/')})`;

    try {
        const res = await fetch(`api/v1/financeiro_stats.php?inicio=${inicio}&fim=${fim}`);
        const json = await res.json();
        if(!json.success) return;

        const d = json.data;

        // Cards
        document.getElementById('val-bruta').innerText = formatMoeda(d.resumo.receita_bruta || 0);
        document.getElementById('val-comissao').innerText = formatMoeda(d.resumo.total_a_pagar || 0);
        document.getElementById('val-paga').innerText = formatMoeda(d.resumo.receita_paga || 0);
        document.getElementById('val-vip').innerText = d.resumo.total_vip || 0;

        // Tabela Barbeiros
        const listB = document.getElementById('lista-barbeiros');
        listB.innerHTML = d.barbeiros.map(b => {
            const nome = b.barbeiro || 'Profissional';
            return `
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div class="barber-avatar">${nome.substring(0,2).toUpperCase()}</div>
                        <b>${nome}</b>
                    </div>
                </td>
                <td align="center">${b.total_servicos}</td>
                <td align="center"><span class="badge" style="background:rgba(245, 166, 35, 0.1); color:var(--warning); padding:4px 10px; border-radius:10px;">${b.total_vip} 👑</span></td>
                <td align="right"><b>${formatMoeda(b.total_gerado || 0)}</b></td>
                <td align="right" style="color:var(--secondary); font-weight:bold;">${formatMoeda(b.total_a_pagar || 0)}</td>
                <td align="right">
                    <button onclick="pagarComissao(${b.id}, ${b.total_a_pagar}, '${nome}')" class="bt-button" style="padding:5px 12px; font-size:11px; background:#2ecc71;">
                        <i class="fa-solid fa-hand-holding-dollar"></i> PAGAR
                    </button>
                </td>
            </tr>
        `}).join('') || '<tr><td colspan="6" class="text-center">Nenhum dado financeiro.</td></tr>';

        // Lista Serviços
        const listS = document.getElementById('lista-servicos');
        listS.innerHTML = d.servicos.map(s => `
            <div style="padding:15px 0; border-bottom:1px solid rgba(255,255,255,0.02); display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <b style="display:block; font-size:14px;">${s.descricao}</b>
                    <span style="font-size:11px; color:var(--text3);">${s.quantidade} vendas</span>
                </div>
                <b style="color:var(--secondary)">${formatMoeda(s.subtotal)}</b>
            </div>
        `).join('') || '<p class="text-center">Sem vendas.</p>';

        // Lista Pendências (Auditoria)
        const listP = document.getElementById('lista-pendencias');
        listP.innerHTML = d.pendencias.map(p => `
            <tr>
                <td>
                    <span style="color:var(--text3); font-size:12px;">${p.finalizada_em ? p.finalizada_em.split(' ')[1].substring(0,5) : '--:--'}</span>
                </td>
                <td>
                    <b style="font-size:13px; color:var(--secondary);">${p.barbeiro_nome}</b>
                </td>
                <td>
                    <b style="display:block;">${p.nome_cliente || 'Cliente de Porta'}</b>
                    <small style="color:var(--text3)">${p.servicos_desc}</small>
                    ${p.is_promo == 1 ? '<span class="badge" style="background:rgba(29, 180, 255, 0.1); color:var(--secondary); font-size:9px; padding:2px 5px; margin-left:5px;">💎 PROMO</span>' : ''}
                </td>
                <td align="right"><b style="color:var(--warning)">${formatMoeda(p.valor_total)}</b></td>
                <td align="right">
                    <button onclick="confirmarRecebimento('${p.uuid}')" class="bt-button bt-success" style="padding:5px 12px; font-size:11px;">
                        <i class="fa-solid fa-check"></i> BAIXAR
                    </button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" align="center" style="padding:40px; color:var(--text3);">✅ Tudo em dia! Nenhuma pendência de conferência.</td></tr>';

        // Lista Histórico
        const listH = document.getElementById('lista-historico');
        listH.innerHTML = d.historico.map(h => `
            <tr>
                <td>
                    <span style="font-size:12px;">${new Date(h.data_pagamento).toLocaleString('pt-BR')}</span>
                </td>
                <td>
                    <b style="font-size:13px; color:var(--primary);">${h.barbeiro_nome}</b>
                </td>
                <td>
                    <span style="font-size:12px; color:var(--text3);">${h.data_inicio.split('-').reverse().join('/')} até ${h.data_fim.split('-').reverse().join('/')}</span>
                </td>
                <td align="right"><b style="color:var(--success)">${formatMoeda(h.valor)}</b></td>
                <td align="right">
                    <small style="color:var(--text3)">${h.admin_nome}</small>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" align="center" style="padding:40px; color:var(--text3);">Nenhum pagamento registrado.</td></tr>';

    } catch (e) { console.error(e); }
}

async function pagarComissao(operadorId, valor, barbeiroNome) {
    if (valor <= 0) return alert("Não há comissão para este barbeiro no período.");
    const { inicio, fim } = getDatas();

    if (!confirm(`Confirmar o pagamento de ${formatMoeda(valor)} para ${barbeiroNome} referente ao período de ${inicio} a ${fim}?`)) return;

    try {
        const res = await fetch(`api/v1/financeiro_stats.php?action=pagar_comissao`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                operador_id: operadorId,
                valor: valor,
                data_inicio: inicio,
                data_fim: fim
            })
        });
        const json = await res.json();
        if (json.success) {
            alert("Pagamento registrado com sucesso!");
            carregarDados();
        } else {
            alert("Erro: " + (json.message || "Desconhecido"));
        }
    } catch(e) { alert("Erro na conexão."); }
}

async function confirmarRecebimento(uuid) {
    if (!confirm("Confirmar que este valor entrou no caixa?")) return;

    try {
        const res = await fetch('api/v1/agenda.php?action=confirm_pay', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ uuid })
        });
        const json = await res.json();
        if (json.success) {
            carregarDados();
        }
    } catch(e) { alert("Erro na conexão."); }
}

document.addEventListener('DOMContentLoaded', carregarDados);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
