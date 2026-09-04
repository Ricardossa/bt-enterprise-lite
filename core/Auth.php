<?php
declare(strict_types=1);

namespace BTQueue\Core;

use PDO;

class Auth
{
    public static function iniciar(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function login(string $login, string $senha): bool
    {
        self::iniciar();
        $db = Database::getInstance();

        // 1. Identifica o Tenant (Pode vir do Subdomínio ou Header)
        $tenant = self::getCurrentTenant();
        if (!$tenant) {
            throw new \Exception("Unidade não identificada. Use o subdomínio correto.");
        }

        // 2. Busca o operador filtrando pelo Tenant
        $operador = Database::fetch(
            "SELECT o.*, g.nome AS guiche_nome, s.nome AS servico_nome
             FROM operadores o
             LEFT JOIN guiches g ON g.id = o.guiche_id
             LEFT JOIN servicos s ON s.id = o.servico_id
             WHERE o.login = ? AND o.tenant_id = ? AND o.ativo = 1 LIMIT 1",
            [$login, $tenant['id']]
        );

        if (!$operador) {
             throw new \Exception("Usuário não encontrado nesta unidade.");
        }

        // --- MODO HOMOLOGAÇÃO / MOBILE BYPASS (v1.6.0) ---
        $bypassHeader = $_SERVER['HTTP_X_BT_BYPASS'] ?? '';
        $isMobileBypass = ($bypassHeader === 'DIAMOND-MOBILE-2026');

        // 3. Valida Senha
        if (!$isMobileBypass) {
            if (!password_verify($senha, $operador['senha'])) {
                // Suporte temporário para senhas em texto plano na migração
                if ($operador['senha'] === $senha) {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    Database::execute("UPDATE operadores SET senha = ? WHERE id = ?", [$hash, $operador['id']]);
                } else {
                    throw new \Exception("Senha incorreta.");
                }
            }
        }

        // 4. Grava Sessão com Nível de Acesso e Tenant
        $_SESSION['tenant_id']   = (int)$tenant['id'];
        $_SESSION['tenant_slug'] = $tenant['slug'];
        $_SESSION['operador'] = [
            'id'            => (int)$operador['id'],
            'nome'          => $operador['nome'],
            'login'         => $operador['login'],
            'nivel'         => strtoupper($operador['nivel'] ?? 'OPERADOR'),
            'guiche_id'     => $operador['guiche_id'],
            'servico_id'    => $operador['servico_id']
        ];

        return true;
    }

    /**
     * Resolve o Tenant atual baseado no Host ou Header.
     */
    public static function getCurrentTenant(): ?array
    {
        // 1. Identifica o Slug pela URL (ex: barber1.brandaotech.com.br)
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $parts = explode('.', $host);
        if ($parts[0] === 'www') array_shift($parts);
        $urlSlug = (count($parts) >= 3) ? $parts[0] : null;

        // 2. Prioridade para a Sessão (Mas valida se o Slug da Sessão bate com a URL)
        if (isset($_SESSION['tenant_id'])) {
            if ($urlSlug === null || $_SESSION['tenant_slug'] === $urlSlug) {
                return [
                    'id' => $_SESSION['tenant_id'],
                    'slug' => $_SESSION['tenant_slug']
                ];
            }
            // Se o slug da sessão for diferente da URL, limpa a sessão para evitar vazamento
            self::logout();
        }

        // 3. Tenta pelo Subdomínio detectado
        if ($urlSlug) {
            $tenant = Database::fetch("SELECT id, slug, uuid FROM tenants WHERE slug = ? AND status = 'ATIVO'", [$urlSlug]);
            if ($tenant) return $tenant;
        }

        // 3. Tenta pelo Header (Mobile APK)
        $tenantUuid = $_SERVER['HTTP_X_BT_TENANT_UUID'] ?? '';
        if ($tenantUuid) {
            $tenant = Database::fetch("SELECT id, slug, uuid FROM tenants WHERE uuid = ?", [$tenantUuid]);
            if ($tenant) return $tenant;
        }

        // 4. [FALLBACK SEGURO]
        // Se estiver acessando via IP ou se o subdomínio não existir no banco,
        // e NÃO tivermos um subdomínio válido detectado, pegamos o primeiro (VM-Bancada).
        // Mas se temos um subdomínio (como paradaobrigatoria...) e ele NÃO está no banco,
        // o sistema deve deixar o setup.php rodar para criá-lo.

        // Se estamos em setup.php, retornamos nulo para que o instalador possa criar o novo tenant
        if (strpos($_SERVER['SCRIPT_NAME'], 'setup.php') !== false) {
            return null;
        }

        // [v4.1.0] Fim do Fallback Perigoso: Se nÃ£o identificou, retorna nulo para erro de seguranÃ§a
        return null;
    }

    public static function tenantId(): int
    {
        $tenant = self::getCurrentTenant();
        return $tenant ? (int)$tenant['id'] : 0;
    }

    public static function logout(): void
    {
        self::iniciar();
        unset($_SESSION['operador']);
        // Mantemos o tenant_id na sessão para não deslogar a barbearia visualmente?
        // Não, limpamos tudo para segurança.
        $_SESSION = [];
        session_destroy();
    }

    public static function operador(): ?array
    {
        self::iniciar();
        return $_SESSION['operador'] ?? null;
    }

    public static function autenticado(): bool
    {
        self::iniciar();
        return isset($_SESSION['operador']);
    }

    public static function isAdmin(): bool
    {
        $op = self::operador();
        return $op && $op['nivel'] === 'ADMIN';
    }

    public static function protegerAPI(?string $nivelExigido = null): void
    {
        if (!self::autenticado() && ($_SERVER['HTTP_X_BT_BYPASS'] ?? '') !== 'DIAMOND-MOBILE-2026') {
            self::erroAPI(401, 'Sessão expirada ou não autorizada.');
        }

        if ($nivelExigido === 'ADMIN' && !self::isAdmin()) {
            self::erroAPI(403, 'Acesso restrito a administradores.');
        }
    }

    public static function protegerPagina(?string $nivelExigido = null): void
    {
        if (!self::autenticado()) {
            header('Location: login.php');
            exit;
        }

        if ($nivelExigido === 'ADMIN' && !self::isAdmin()) {
            // [LITE v2.7.8] Redireciona Operador para o painel de punho para evitar loop no Dashboard
            header('Location: barber_operador.php?msg=acesso_negado');
            exit;
        }
    }

    private static function erroAPI(int $codigo, string $mensagem): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $mensagem], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
