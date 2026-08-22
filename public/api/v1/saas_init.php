<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: application/json');

try {
    // 1. Garante que o Tenant 'lite' existe
    $exists = Database::fetch("SELECT id FROM tenants WHERE slug = 'lite' LIMIT 1");

    if (!$exists) {
        Database::execute(
            "INSERT INTO tenants (id, uuid, slug, nome, status) VALUES (1, 'LITE-DIAMOND-001', 'lite', 'Barbearia Lite Oficial', 'ATIVO')"
        );
        echo json_encode(['success' => true, 'message' => 'Tenant LITE inicializado!']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Tenant LITE já existe.']);
    }

    // 2. Vincula operadores orfãos ao tenant 1
    Database::execute("UPDATE operadores SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0");
    Database::execute("UPDATE servicos SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0");
    Database::execute("UPDATE guiches SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0");
    Database::execute("UPDATE senhas SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0");
    Database::execute("UPDATE configuracoes SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0");
    Database::execute("UPDATE promocoes SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0");
    Database::execute("UPDATE agenda_regras SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0");
    Database::execute("UPDATE agenda_suspensoes SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0");
    Database::execute("UPDATE agenda_bloqueios SET tenant_id = 1 WHERE tenant_id IS NULL OR tenant_id = 0");

    // 3. Garante configurações de Checkout SaaS (v3.3.7)
    $saasConfigs = [
        ['payment_strategy', 'manual', 'STRING', 'Estratégia de cobrança: mercadopago, manual ou disabled'],
        ['booking_release_mode', 'immediate', 'STRING', 'Liberação do horário: immediate (agora) ou after_payment (após PIX)'],
        ['pix_chave_estatica', '71991077018', 'STRING', 'Chave PIX fixa para recebimentos manuais']
    ];
    foreach($saasConfigs as $sc) {
        $exists = Database::fetch("SELECT chave, valor FROM configuracoes WHERE chave = ? AND tenant_id = 1 LIMIT 1", [$sc[0]]);
        if (!$exists) {
            Database::execute("INSERT INTO configuracoes (tenant_id, chave, valor, tipo, descricao) VALUES (1, ?, ?, ?, ?)", $sc);
        } else {
            // v3.3.7: Se a estratégia estiver como 'mercadopago' mas não houver token, força 'manual'
            if ($sc[0] === 'payment_strategy' && $exists['valor'] === 'mercadopago') {
                $token = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'mercadopago_token' AND tenant_id = 1 LIMIT 1")['valor'] ?? '';
                if (empty($token)) {
                    Database::execute("UPDATE configuracoes SET valor = 'manual' WHERE chave = 'payment_strategy' AND tenant_id = 1");
                }
            }
        }
    }

    // 4. CURA DE OPERADOR (Garante que as regras do ID 1 ou outros vão para o Ricardo ID 2)
    Database::execute("UPDATE agenda_regras SET operador_id = 2 WHERE operador_id != 2");

    echo json_encode(['success' => true, 'message' => 'Ecossistema LITE sincronizado com sucesso!']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
