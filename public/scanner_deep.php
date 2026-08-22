<?php
/**
 * 🕵️ DEEP SCANNER - BUSCA POR DADOS PERDIDOS
 */
declare(strict_types=1);

$filesToCheck = [
    'Y:/bt-platform/99_QUARENTENA/root_leftovers/03_DISTRIBUICAO_E_SETUP/BT_Queue_Enterprise/www/database/banco.db.db',
    'Y:/bt-platform/99_QUARENTENA/root_leftovers/99_BACKUPS_ANTIGOS/OLD_BACKUPS/BACKUP_ARQUITETO_FINAL_20260724/database/banco.db',
    'Y:/bt-platform/99_QUARENTENA/root_leftovers/99_BACKUPS_ANTIGOS/OLD_BACKUPS/BACKUP_ARQUITETO_FINAL_20260724/database/banco.db.db',
    'Y:/bt-platform/99_QUARENTENA/root_leftovers/99_BACKUPS_ANTIGOS/OLD_BACKUPS/BACKUP_SISTEMA_INTEGRADO/Enterprise_V4/database/banco.db',
    'Y:/bt-platform/99_QUARENTENA/root_leftovers/BACKUP_FINANCEIRO_SEGURANCA/banco_v4_sqlite.db',
    'Y:/bt-platform/99_QUARENTENA/root_leftovers/BACKUP_FINANCEIRO_SEGURANCA/public/painel_v4/database/banco.db',
    'Y:/bt-platform/99_QUARENTENA/root_leftovers/BACKUP_FINANCEIRO_SEGURANCA/public/painel_v4/database/banco.db.db',
    'Y:/bt-platform/99_QUARENTENA/root_leftovers/BACKUP_OTA_AUDIT/banco_v4.db',
    'Y:/bt-platform/99_QUARENTENA/root_leftovers/BACKUP_OTA_AUDIT/public/painel_v4/database/banco.db',
    'Y:/bt-platform/99_QUARENTENA/root_leftovers/BACKUP_OTA_AUDIT/public/painel_v4/database/banco.db.db',
    'Y:/bt-platform/99_QUARENTENA/root_leftovers/DISTRIBUICAO/BT_Queue_Enterprise/www/database/banco.db.db'
];

echo "<html><body style='background:#081421; color:#fff; font-family:sans-serif; padding:20px;'>";
echo "<h1>🕵️ Deep Scanner - Buscando Piu, João e Cervejas</h1>";

foreach ($filesToCheck as $path) {
    echo "<div style='background:#132238; border:1px solid #1e3552; padding:15px; border-radius:10px; margin-bottom:15px;'>";
    echo "<b style='color:#1db4ff;'>Analisando:</b> " . htmlspecialchars($path) . "<br>";

    if (!file_exists($path)) {
        echo "<span style='color:#ff4d4d;'>Arquivo não encontrado.</span></div>";
        continue;
    }

    try {
        $db = new PDO("sqlite:$path");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $results = [];

        // 1. Busca Operadores
        try {
            $stmt = $db->query("SELECT nome FROM operadores WHERE nome LIKE '%Piu%' OR nome LIKE '%João%'");
            $ops = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($ops)) $results[] = "✅ <b>Operadores:</b> " . implode(', ', $ops);
        } catch (Exception $e) {}

        // 2. Busca Promoções
        try {
            $stmt = $db->query("SELECT titulo FROM promocoes WHERE titulo LIKE '%cerveja%' OR descricao LIKE '%cerveja%'");
            $promos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($promos)) $results[] = "🍺 <b>Promoções:</b> " . implode(', ', $promos);
        } catch (Exception $e) {}

        // 3. Busca Guichês
        try {
            $stmt = $db->query("SELECT nome FROM guiches WHERE nome LIKE '%Piu%' OR nome LIKE '%João%' OR nome LIKE '%Ricardo%'");
            $guiches = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($guiches)) $results[] = "⚙️ <b>Guichês:</b> " . implode(', ', $guiches);
        } catch (Exception $e) {}

        if (!empty($results)) {
            echo "<div style='margin-top:10px; color:#18c964;'>" . implode('<br>', $results) . "</div>";
            echo "<br><a href='migrar_bkp.php?source=" . urlencode($path) . "' style='background:#18c964; color:#fff; padding:8px 15px; text-decoration:none; border-radius:5px; font-weight:bold; display:inline-block; margin-top:10px;'>MIGRAR ESTE BANCO</a>";
        } else {
            echo "<span style='color:#94a3b8;'>Nenhum dado correspondente encontrado.</span>";
        }

    } catch (Exception $e) {
        echo "<span style='color:#f5a623;'>Erro na leitura: " . $e->getMessage() . "</span>";
    }
    echo "</div>";
}
echo "</body></html>";
