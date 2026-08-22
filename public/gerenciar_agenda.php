<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;

Auth::protegerPagina('ADMIN');

// [LITE v2.6.2] Recursos de agenda sempre liberados no Lite
$hasAgenda = true;

$tenantId = Auth::tenantId();
$barbeiros = Database::fetchAll("SELECT id, nome FROM operadores WHERE tenant_id = ? AND ativo = 1 AND nivel = 'OPERADOR' ORDER BY nome ASC", [$tenantId]);

$pageTitle = 'Gerenciar Agenda';
include __DIR__ . '/includes/header.php';
?>

<style>
    .agenda-manager-card { background: var(--card); border-radius: 15px; border: 1px solid var(--border); padding: 25px; margin-top: 20px; }
    .day-row { display: grid; grid-template-columns: 150px 100px 100px 80px 150px 80px 60px; gap: 15px; align-items: center; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .day-name { font-weight: bold; color: var(--secondary); text-transform: uppercase; font-size: 13px; }
    .lib-box { font-size: 11px; background: rgba(0,0,0,0.2); padding: 5px; border-radius: 5px; }
</style>

<main class="bt-main">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2><i class="fa-solid fa-calendar-check"></i> Gestão de Agendamentos</h2>
            <p style="color:var(--text2); font-size:14px;">Configure as janelas de horário e duração dos atendimentos.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="agendar.php" target="_blank" class="bt-button" style="background:var(--sidebar); border:1px solid var(--border); text-decoration:none;">
                <i class="fa-solid fa-external-link"></i> VER PÁGINA PÚBLICA
            </a>
            <button id="btnSalvar" class="bt-button bt-success">
                <i class="fa-solid fa-floppy-disk"></i> SALVAR CONFIGURAÇÃO
            </button>
        </div>
    </div>

    <div class="agenda-manager-card" style="border-left: 4px solid var(--secondary); margin-bottom: 25px;">
        <div style="font-size: 14px; font-weight: bold; color: var(--secondary); margin-bottom: 20px;">
            <i class="fa-solid fa-gears"></i> CONFIGURAÇÃO GLOBAL DA AGENDA
        </div>
        <div class="form-group" style="max-width: 400px;">
            <label>Horizonte de Agendamento (Dias Futuros)</label>
            <input type="number" id="agenda_horizonte" class="form-control" value="30" min="1" max="365">
            <small style="color:var(--text2); font-size:11px;">Define quantos dias o cliente consegue ver no calendário.</small>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:20px; border-top:1px solid rgba(255,255,255,0.05); padding-top:20px;">
            <div class="form-group">
                <label>Radar de Faltas (Automático)</label>
                <select id="radar_enabled" class="form-control">
                    <option value="0">Desativado (Anistia)</option>
                    <option value="1">Ativado (Vigilância)</option>
                </select>
                <small style="color:var(--text2); font-size:10px;">Marca como 'FALTOU' e suspende quem atrasar.</small>
            </div>
            <div class="form-group">
                <label>Tolerância (Minutos)</label>
                <input type="number" id="radar_tolerance" class="form-control" value="15" min="5" max="120">
                <small style="color:var(--text2); font-size:10px;">Tempo antes de aplicar a falta automática.</small>
            </div>
        </div>
    </div>

    <!-- MÓDULO DE COMPLIANCE E SUSPENSÕES (v6.3) -->
    <div class="agenda-manager-card" style="border-left: 4px solid var(--danger);">
        <div style="font-size: 14px; font-weight: bold; color: var(--danger); margin-bottom: 20px;">
            <i class="fa-solid fa-user-slash"></i> CONTROLE DE PENALIDADES (COMPLIANCE)
        </div>
        <p style="color: var(--text2); font-size: 13px; margin-bottom: 20px;">Representantes que não comparecerem sem aviso podem ser suspensos aqui.</p>

        <div style="display: flex; gap: 15px; margin-bottom: 25px; align-items: flex-end;">
            <div style="flex: 2;">
                <label class="form-label-small">Nome Completo do Fornecedor</label>
                <input type="text" id="suspend-nome" class="form-control" placeholder="Digite o nome exatamente como no agendamento">
            </div>
            <div style="flex: 1;">
                <label class="form-label-small">Dias de Suspensão</label>
                <select id="suspend-dias" class="form-control">
                    <option value="14">14 Dias</option>
                    <option value="30">30 Dias</option>
                    <option value="60">60 Dias</option>
                </select>
            </div>
            <button onclick="addSuspension()" class="bt-button bt-danger" style="padding: 12px 20px;">SUSPENDER ACESSO</button>
        </div>

        <table class="stats-table">
            <thead>
                <tr>
                    <th>Fornecedor Suspenso</th>
                    <th>Motivo</th>
                    <th>Até Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="lista-suspensoes">
                <!-- Injetado via JS -->
            </tbody>
        </table>
    </div>

    <div class="agenda-manager-card">
        <div class="form-group" style="max-width: 400px; margin-bottom: 30px;">
            <label>Selecione o Profissional para Configurar</label>
            <select id="select-operador" class="form-control">
                <option value="">Escolha um barbeiro...</option>
                <?php foreach ($barbeiros as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="regras-container" class="hidden">
            <div class="day-row" style="border-bottom: 2px solid var(--border); padding-bottom: 10px; opacity: 0.6;">
                <div class="day-name">Dia da Semana</div>
                <div>Início</div>
                <div>Fim</div>
                <div>Slot</div>
                <div>Liberação (Opcional)</div>
                <div>Ativo</div>
            </div>

            <?php
            $dias = ["Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"];
            foreach ($dias as $index => $nome): ?>
                <div class="day-row" data-dia="<?= $index ?>">
                    <div class="day-name"><?= $nome ?></div>
                    <div><input type="time" class="form-control start-time" value="08:00"></div>
                    <div><input type="time" class="form-control end-time" value="18:00"></div>
                    <div><input type="number" class="form-control slot-duration" value="30"></div>
                    <div class="lib-box">
                        <select class="form-control lib-dia" style="font-size:10px; padding:2px;">
                            <option value="">Sempre Aberto</option>
                            <?php foreach ($dias as $idx => $n): ?>
                                <option value="<?= $idx ?>">Só abre na <?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div style="display:flex; gap:2px; margin-top:5px;">
                            <input type="time" class="form-control lib-inicio" value="00:00" style="font-size:9px; padding:2px;">
                            <input type="time" class="form-control lib-fim" value="23:59" style="font-size:9px; padding:2px;">
                        </div>
                    </div>
                    <div style="text-align:center;">
                        <input type="checkbox" class="is-active" checked style="transform: scale(1.3);">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="no-selection" style="padding: 60px; text-align: center; color: var(--text3);">
            <i class="fa-solid fa-mouse-pointer" style="font-size: 40px; margin-bottom: 20px;"></i>
            <p>Selecione um serviço acima para começar a configurar a agenda.</p>
        </div>
    </div>
</main>

<script>
    const $dom = {
        select: document.getElementById('select-operador'),
        agendaHorizonte: document.getElementById('agenda_horizonte'),
        radarEnabled: document.getElementById('radar_enabled'),
        radarTolerance: document.getElementById('radar_tolerance'),
        container: document.getElementById('regras-container'),
        noSelection: document.getElementById('no-selection'),
        btnSalvar: document.getElementById('btnSalvar'),
        rows: document.querySelectorAll('.day-row[data-dia]')
    };

    $dom.select.onchange = async () => {
        const id = $dom.select.value;
        if (!id) {
            $dom.container.classList.add('hidden');
            $dom.noSelection.classList.remove('hidden');
            return;
        }

        $dom.container.classList.remove('hidden');
        $dom.noSelection.classList.add('hidden');

        // Carrega regras existentes + horizonte global
        try {
            const res = await fetch(`api/v1/agenda.php?action=get_regras&operador_id=${id}`);
            const json = await res.json();

            // Reseta para o padrão antes de aplicar os dados do banco
            resetFields();

            if (json.success) {
                if ($dom.agendaHorizonte) $dom.agendaHorizonte.value = json.horizonte || 30;
                if ($dom.radarEnabled) $dom.radarEnabled.value = json.radar_enabled || "0";
                if ($dom.radarTolerance) $dom.radarTolerance.value = json.radar_tolerance || "15";

                if (json.data && json.data.length > 0) {
                    json.data.forEach(regra => {
                        const row = document.querySelector(`.day-row[data-dia="${regra.dia_semana}"]`);
                        if (row) {
                            row.querySelector('.start-time').value = regra.hora_inicio;
                            row.querySelector('.end-time').value = regra.hora_fim;
                            row.querySelector('.slot-duration').value = regra.duracao_slot;
                            row.querySelector('.lib-dia').value = regra.liberacao_dia_semana !== null ? regra.liberacao_dia_semana : "";
                            row.querySelector('.lib-inicio').value = regra.liberacao_hora_inicio || "00:00";
                            row.querySelector('.lib-fim').value = regra.liberacao_hora_fim || "23:59";
                            row.querySelector('.is-active').checked = parseInt(regra.ativo) === 1;
                        }
                    });
                }
            }
        } catch (e) { console.error(e); }
    };

    function resetFields() {
        $dom.rows.forEach(row => {
            row.querySelector('.start-time').value = "08:00";
            row.querySelector('.end-time').value = "18:00";
            row.querySelector('.slot-duration').value = "30";
            row.querySelector('.lib-dia').value = "";
            row.querySelector('.lib-inicio').value = "00:00";
            row.querySelector('.lib-fim').value = "23:59";
            row.querySelector('.is-active').checked = false;
        });
    }

    $dom.btnSalvar.onclick = async () => {
        const operadorId = $dom.select.value;
        if (!operadorId) return alert("Selecione um profissional primeiro.");

        const regras = [];
        $dom.rows.forEach(row => {
            regras.push({
                dia_semana: row.getAttribute('data-dia'),
                hora_inicio: row.querySelector('.start-time').value,
                hora_fim: row.querySelector('.end-time').value,
                duracao_slot: row.querySelector('.slot-duration').value,
                liberacao_dia: row.querySelector('.lib-dia').value,
                liberacao_inicio: row.querySelector('.lib-inicio').value,
                liberacao_fim: row.querySelector('.lib-fim').value,
                ativo: row.querySelector('.is-active').checked ? 1 : 0
            });
        });

        $dom.btnSalvar.disabled = true;
        $dom.btnSalvar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> SALVANDO...';

        const rulesPayload = {
            operador_id: operadorId,
            regras: regras,
            horizonte: $dom.agendaHorizonte ? $dom.agendaHorizonte.value : 30,
            radar_enabled: $dom.radarEnabled ? $dom.radarEnabled.value : "0",
            radar_tolerance: $dom.radarTolerance ? $dom.radarTolerance.value : "15"
        };

        try {
            const res = await fetch('api/v1/agenda.php?action=save_regras', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(rulesPayload)
            });

            const json = await res.json();

            if (json.success) {
                BT.toast.sucesso("Agenda atualizada com sucesso!");
            } else {
                alert("Erro: " + (json.message || "Falha desconhecida"));
            }
        } catch (e) {
            console.error(e);
            alert("Erro crítico na comunicação com o servidor.");
        }

        $dom.btnSalvar.disabled = false;
        $dom.btnSalvar.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> SALVAR CONFIGURAÇÃO';
    };

    // --- FUNÇÕES DE SUSPENSÃO (v6.3) ---
    async function loadSuspensions() {
        const res = await fetch('api/v1/agenda.php?action=get_suspensoes');
        const json = await res.json();
        const body = document.getElementById('lista-suspensoes');
        body.innerHTML = json.data.map(s => `
            <tr>
                <td><b>${s.identificador}</b></td>
                <td><small>${s.motivo}</small></td>
                <td>${new Date(s.data_fim).toLocaleDateString()}</td>
                <td>
                    <button onclick="removeSuspension(${s.id})" class="bt-button bt-danger" style="padding:5px 10px; font-size:10px;">LIBERAR</button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="4" class="text-center">Nenhuma suspensão ativa.</td></tr>';
    }

    window.addSuspension = async () => {
        const nome = document.getElementById('suspend-nome').value.trim();
        const dias = document.getElementById('suspend-dias').value;
        if (!nome) return alert("Digite o nome do fornecedor.");

        const res = await fetch('api/v1/agenda.php?action=add_suspensao', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ nome, dias })
        });
        const json = await res.json();
        if (json.success) {
            document.getElementById('suspend-nome').value = '';
            loadSuspensions();
            BT.toast.sucesso(json.message);
        }
    };

    window.removeSuspension = async (id) => {
        if (!confirm("Deseja realmente liberar este acesso?")) return;
        await fetch('api/v1/agenda.php?action=remove_suspensao', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id })
        });
        loadSuspensions();
    };

    loadSuspensions();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
