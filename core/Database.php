<?php

declare(strict_types=1);

namespace BTQueue\Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = Config::get('database');

            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    $config['host'],
                    $config['dbname'],
                    $config['charset']
                );

                self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);

                // Configurações Multi-Tenant de Performance
                self::$instance->exec("SET NAMES utf8mb4");
                self::$instance->exec("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");

            } catch (PDOException $e) {
                throw new Exception('Erro ao conectar ao MariaDB SaaS: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }


    public static function begin(): void
    {
        self::getInstance()->beginTransaction();
    }

    public static function beginImmediate(): void
    {
        // MariaDB usa START TRANSACTION. Para modo imediato usamos LOCK TABLES se necessário,
        // mas por padrão START TRANSACTION é suficiente para ACID.
        self::getInstance()->beginTransaction();
    }

    public static function commit(): void
    {
        if (self::getInstance()->inTransaction()) {
            self::getInstance()->commit();
        }
    }

    public static function rollback(): void
    {
        if (self::getInstance()->inTransaction()) {
            self::getInstance()->rollBack();
        }
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = self::getInstance()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function lastInsertId(): int
    {
        return (int) self::getInstance()->lastInsertId();
    }

    public static function getTableColumns(string $table): array
    {
        try {
            // Padrão MariaDB/MySQL
            $stmt = self::getInstance()->query("DESCRIBE $table");
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function exists(): bool
    {
        try {
            // No MariaDB, verificamos se a tabela de sistema existe
            $res = self::getInstance()->query("SHOW TABLES LIKE 'system_info'");
            return $res->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
