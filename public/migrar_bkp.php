<?php
/**
 * 🛠️ SCRIPT DE MIGRAÇÃO DE BACKUP (SQLite -> MariaDB)
 * Recupera dados do banco.db (backup) e injeta no MariaDB SaaS.
 */
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

$sqlitePath = dirname(__DIR__) . '/bt-enterprise-lite/database/banco.db';

if (!file_exists($sqlitePath)) {
    die("<h1>❌ ERRO:</h1> Arquivo de backup não encontrado em: $sqlitePath");
}

try {
    $sqlite = new PDO("sqlite:$sqlitePath");
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $mariadb = Database::getInstance();

    // Identifica o Tenant ID ativo (conforme force_active.php anterior)
    $tenant = $mariadb->query("SELECT id FROM tenants ORDER BY id DESC LIMIT 1")->fetch();
    if (!$tenant) die("<h1>❌ ERRO:</h1> Nenhum Tenant encontrado no MariaDB. Rode o setup primeiro.");
    $tenantId = (int)$tenant['id'];

    echo "<h1>🛠️ Iniciando Migração para Tenant ID: $tenantId</h1>";

    $tablesToMigrate = [
        'servicos' => ['codigo', 'nome', 'slug', 'prefixo', 'icone', 'cor', 'ordem', 'tempo_medio', 'preco', 'ativo'],
        'guiches' => ['codigo', 'nome', 'icone', 'cor', 'ativo'],
        'operadores' => ['nome', 'login', 'senha', 'nivel', 'guiche_id', 'servico_id', 'prefixo', 'foto_url', 'ativo'],
        'agenda_regras' => ['operador_id', 'servico_id', 'dia_semana', 'hora_inicio', 'hora_fim', 'duracao_slot', 'liberacao_dia_semana', 'liberacao_hora_inicio', 'liberacao_hora_fim', 'ativo'],
        'agenda_bloqueios' => ['data', 'hora_inicio', 'hora_fim', 'motivo'],
        'agenda_suspensoes' => ['identificador', 'motivo', 'data_fim'],
        'configuracoes' => ['chave', 'valor', 'tipo', 'descricao', 'editavel', 'label_cliente']
    ];

    foreach ($tablesToMigrate as $table => $cols) {
        echo "<h2>Migrando tabela: $table ...</h2>";

        // Busca no SQLite
        try {
            $data = $sqlite->query("SELECT * FROM $table")->fetchAll();
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠ Aviso: Tabela $table não encontrada no SQLite ou erro na leitura: {$e->getMessage()}</p>";
            continue;
        }

        if (empty($data)) {
            echo "<p>ℹ Tabela vazia no backup.</p>";
            continue;
        }

        $count = 0;
        foreach ($data as $row) {
            // Prepara a query de inserção no MariaDB
            $fields = ['tenant_id'];
            $placeholders = ['?'];
            $values = [$tenantId];

            foreach ($cols as $col) {
                if (array_key_exists($col, $row)) {
                    $fields[] = $col;
                    $placeholders[] = '?';
                    $values[] = $row[$col];
                }
            }

            $sql = "INSERT IGNORE INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";

            try {
                $stmt = $mariadb->prepare($sql);
                $stmt->execute($values);
                $count++;
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Erro ao inserir registro em $table: " . $e->getMessage() . "</p>";
            }
        }
        echo "<p>✅ <b>$count</b> registros migrados para $table.</p>";
    }

    echo "<h1>✨ MIGRAÇÃO CONCLUÍDA!</h1>";
    echo "<p>Agora você pode voltar ao Dashboard e verificar seus dados.</p>";
    echo "<a href='index.php' style='padding: 10px 20px; background: #1565C0; color: #fff; text-decoration: none; border-radius: 5px;'>IR PARA O DASHBOARD</a>";

} catch (Exception $e) {
    echo "<h1>❌ ERRO CRÍTICO NA MIGRAÇÃO:</h1> " . $e->getMessage();
}
