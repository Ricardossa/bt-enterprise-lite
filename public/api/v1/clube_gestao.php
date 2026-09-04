<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

try {
    $tenantId = Auth::tenantId();
    $action = $_GET['action'] ?? '';

    // --- AÇÕES PÚBLICAS (Mural e Mobile) ---

    if ($action === 'listar_planos') {
        $res = Database::fetchAll("SELECT * FROM clube_planos WHERE tenant_id = ? AND ativo = 1", [$tenantId]);
        echo json_encode(['success' => true, 'data' => $res], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'ver_assinatura_ativa') {
        $clienteId = (int)($_GET['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Cliente não informado.']);
            exit;
        }

        $res = Database::fetch("
            SELECT a.*, p.nome as plano_nome
            FROM clube_assinaturas a
            JOIN clube_planos p ON p.id = a.plano_id
            WHERE a.cliente_id = ? AND a.tenant_id = ? AND a.status = 'ATIVA'
            AND a.data_fim >= CURDATE()
            ORDER BY a.id DESC LIMIT 1
        ", [$clienteId, $tenantId]);

        if ($res) {
            $res['data_fim'] = date('d/m/Y', strtotime($res['data_fim']));
            echo json_encode(['success' => true, 'data' => $res], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    if ($action === 'solicitar_assinatura') {
        // [LITE v2.6.1] Aberto para clientes solicitarem entrada
        $input = json_decode(file_get_contents('php://input'), true);
        $clienteId = (int)($input['cliente_id'] ?? 0);
        $planoId = (int)($input['plano_id'] ?? 0);

        if ($clienteId <= 0 || $planoId <= 0) {
            throw new Exception("Dados incompletos para solicitação.");
        }

        $plano = Database::fetch("SELECT * FROM clube_planos WHERE id = ? AND tenant_id = ?", [$planoId, $tenantId]);
        if (!$plano) throw new Exception("Plano não encontrado.");

        // [v2.6.2] Agora as datas são NULLABLE, permitindo inserção pendente sem erro de SQL
        Database::execute(
            "INSERT INTO clube_assinaturas (tenant_id, cliente_id, plano_id, cortes_restantes, status, created_at)
             VALUES (?, ?, ?, ?, 'PENDENTE', NOW())",
            [$tenantId, $clienteId, $planoId, (int)$plano['qtd_cortes']]
        );

        echo json_encode(['success' => true, 'message' => 'Solicitação enviada! Aguarde a aprovação do barbeiro.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --- AÇÕES PROTEGIDAS (Apenas Admin) ---
    Auth::protegerAPI('ADMIN');

    if ($action === 'salvar_plano') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        $nome = trim($input['nome'] ?? '');
        $preco = (float)str_replace(',', '.', (string)($input['preco'] ?? '0'));
        $qtd = (int)($input['qtd'] ?? 0);

        if (empty($nome)) throw new Exception("Nome do plano é obrigatório.");

        if ($id > 0) {
            $ok = Database::execute(
                "UPDATE clube_planos SET nome = ?, preco = ?, qtd_cortes = ? WHERE id = ? AND tenant_id = ?",
                [$nome, $preco, $qtd, $id, $tenantId]
            );
        } else {
            $ok = Database::execute(
                "INSERT INTO clube_planos (tenant_id, nome, preco, qtd_cortes) VALUES (?, ?, ?, ?)",
                [$tenantId, $nome, $preco, $qtd]
            );
        }
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Plano salvo!' : 'Erro ao gravar no banco.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'remover_plano') {
        $id = (int)$_GET['id'];
        // Se for remoção de assinatura pendente, o ID vem da tabela clube_assinaturas
        // Se for remoção de plano, o ID vem da tabela clube_planos
        // Vamos tratar as duas situações aqui baseado no contexto
        $isAssinatura = isset($_GET['is_assinatura']);
        if ($isAssinatura) {
            $ok = Database::execute("DELETE FROM clube_assinaturas WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        } else {
            $ok = Database::execute("UPDATE clube_planos SET ativo = 0 WHERE id = ? AND tenant_id = ?", [$id, $tenantId]);
        }
        echo json_encode(['success' => $ok]);
        exit;
    }

    if ($action === 'vender_assinatura') {
        $input = json_decode(file_get_contents('php://input'), true);
        $clienteId = (int)$input['cliente_id'];
        $planoId = (int)$input['plano_id'];

        $plano = Database::fetch("SELECT * FROM clube_planos WHERE id = ? AND tenant_id = ?", [$planoId, $tenantId]);
        if (!$plano) throw new Exception("Plano inválido.");

        $dataInicio = date('Y-m-d');
        $dataFim = date('Y-m-d', strtotime("+{$plano['validade_dias']} days"));

        Database::execute(
            "INSERT INTO clube_assinaturas (tenant_id, cliente_id, plano_id, cortes_restantes, data_inicio, data_fim, status)
             VALUES (?, ?, ?, ?, ?, ?, 'ATIVA')",
            [$tenantId, $clienteId, $planoId, $plano['qtd_cortes'], $dataInicio, $dataFim]
        );

        echo json_encode(['success' => true, 'message' => 'Assinatura ativada!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'listar_pendentes') {
        $res = Database::fetchAll("
            SELECT a.*, p.nome as plano_nome, c.nome as cliente_nome, c.whatsapp
            FROM clube_assinaturas a
            JOIN clube_planos p ON p.id = a.plano_id
            JOIN clientes c ON c.id = a.cliente_id
            WHERE a.tenant_id = ? AND a.status = 'PENDENTE'
            ORDER BY a.id DESC
        ", [$tenantId]);
        echo json_encode(['success' => true, 'data' => $res], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'aprovar_assinatura') {
        $id = (int)$_GET['id'];
        $plano = Database::fetch("
            SELECT p.validade_dias
            FROM clube_assinaturas a
            JOIN clube_planos p ON p.id = a.plano_id
            WHERE a.id = ?
        ", [$id]);

        if (!$plano) throw new Exception("Plano não localizado.");

        $dataInicio = date('Y-m-d');
        $dataFim = date('Y-m-d', strtotime("+{$plano['validade_dias']} days"));

        $ok = Database::execute(
            "UPDATE clube_assinaturas SET status = 'ATIVA', data_inicio = ?, data_fim = ? WHERE id = ? AND tenant_id = ?",
            [$dataInicio, $dataFim, $id, $tenantId]
        );
        echo json_encode(['success' => $ok]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
