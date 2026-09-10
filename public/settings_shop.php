<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;

Auth::protegerPagina('ADMIN');

$tenantId = Auth::tenantId();
$tenant = Database::fetch("SELECT * FROM tenants WHERE id = ?", [$tenantId]);

// [LITE v3.1.1] Busca redes sociais na tabela de configuracoes
$configs = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE tenant_id = ?", [$tenantId]);
$meta = [];
foreach($configs as $c) { $meta[$c['chave']] = $c['valor']; }

$pageTitle = 'Configurações da Unidade';
include __DIR__ . '/includes/header.php';
?>

<main class="bt-main">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
        <div>
            <h2><i class="fa-solid fa-store"></i> Gestão da Barbearia</h2>
            <p style="color:var(--text2); font-size:14px;">Personalize a identidade da sua unidade no SaaS.</p>
        </div>
        <button id="btnSalvar" class="bt-button bt-success" style="padding: 12px 25px;">
            <i class="fa-regular fa-floppy-disk"></i> SALVAR ALTERAÇÕES
        </button>
    </div>

    <div class="bt-grid" style="grid-template-columns: 1.5fr 1fr; gap: 30px;">

        <div style="display:flex; flex-direction:column; gap:25px;">
            <section class="bt-card">
                <h3 style="font-size:14px; color:var(--secondary); text-transform:uppercase; margin-bottom:20px;">🎨 Identidade Visual</h3>

                <div class="form-group">
                    <label>Nome da Loja</label>
                    <input type="text" id="nome_fantasia" class="form-control" value="<?= htmlspecialchars($tenant['nome'] ?? '') ?>">
                </div>

                <div class="form-group" style="margin-top:20px;">
                    <label>Logotipo da Unidade</label>
                    <div style="text-align:center; padding:20px; background:var(--sidebar); border-radius:15px; border:1px solid var(--border); margin-top:10px;">
                        <?php
                        $logo = "uploads/tenants/{$tenantId}/logo.png";
                        $logoUrl = file_exists(__DIR__ . '/' . $logo) ? $logo . '?v=' . time() : 'assets/img/logo-placeholder.png';
                        ?>
                        <img id="logoPreview" src="<?= $logoUrl ?>" style="max-height:80px; margin-bottom:15px; display:block; margin-left:auto; margin-right:auto;">
                        <input type="file" id="logoFile" class="form-control" accept="image/png, image/jpeg">
                <small class="text-muted" style="display:block; margin-top:5px; font-size:10px;">
                    <b>Recomendado:</b> 540 × 360 px, fundo transparente para melhor ajuste no Modo Escuro. Formatos: PNG ou WebP.
                </small>
                    </div>
                </div>
            </section>

            <section class="bt-card">
                <h3 style="font-size:14px; color:var(--secondary); text-transform:uppercase; margin-bottom:20px;">📱 Contato e Redes</h3>
                <div class="form-group">
                    <label>WhatsApp de Atendimento</label>
                    <input type="text" id="whatsapp" class="form-control" placeholder="71999999999" value="<?= htmlspecialchars($meta['whatsapp'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-top:20px;">
                    <label>Instagram</label>
                    <input type="text" id="instagram" class="form-control" placeholder="@suabarbearia" value="<?= htmlspecialchars($meta['instagram'] ?? '') ?>">
                </div>
            </section>
        </div>

        <aside>
            <section class="bt-card" style="border-left: 4px solid var(--primary);">
                <h3 style="font-size:14px; color:var(--primary); text-transform:uppercase; margin-bottom:15px;">💎 Plano Atual</h3>
                <div style="font-size:24px; font-weight:900; color:#fff;"><?= strtoupper($tenant['plano'] ?? 'LITE') ?></div>
                <p style="color:var(--text2); font-size:12px; margin-top:5px;">Expira em: <b>31/12/2026</b></p>
                <hr style="margin:15px 0; border-color:var(--border);">
                <button class="bt-button" style="width:100%; font-size:11px; background:var(--sidebar); border:1px solid var(--border);">
                    FAZER UPGRADE DO PLANO
                </button>
            </section>

            <div class="bt-card" style="margin-top:25px; background:rgba(255, 193, 7, 0.05); border: 1px solid var(--warning);">
                <h4 style="color:var(--warning); margin-bottom:10px;"><i class="fa-solid fa-circle-info"></i> Dica Diamond</h4>
                <p style="font-size:12px; color:var(--text2); line-height:1.4;">
                    Sua URL exclusiva de agendamento é:<br>
                    <b style="color:var(--secondary);">https://<?= $tenant['slug'] ?>.brandaotech.com.br</b>
                </p>
            </div>
        </aside>

    </div>
</main>

<script>
document.getElementById('btnSalvar').onclick = async () => {
    const btn = document.getElementById('btnSalvar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> SALVANDO...';

    const dados = {
        nome: document.getElementById('nome_fantasia').value.trim(),
        whatsapp: document.getElementById('whatsapp').value.trim(),
        instagram: document.getElementById('instagram').value.trim()
    };

    try {
        // Enviar para API de perfil do Tenant
        const res = await fetch('api/v1/tenant_profile.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(dados)
        });
        const json = await res.json();

        if (json.success) {
            // Upload de Logo se houver
            const file = document.getElementById('logoFile').files[0];
            if (file) {
                const formData = new FormData();
                formData.append('logo', file);
                await fetch('api/v1/tenant_logo.php', { method: 'POST', body: formData });
            }
            alert("Configurações da barbearia atualizadas!");
            location.reload();
        } else {
            alert(json.message);
        }
    } catch(e) { alert("Erro ao salvar."); }
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-regular fa-floppy-disk"></i> SALVAR ALTERAÇÕES';
};
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
