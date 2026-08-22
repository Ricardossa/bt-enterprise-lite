<?php

declare(strict_types=1);

namespace BTQueue\Core;

use RuntimeException;

class Config
{
    private static ?array $config = null;

    private static function load(): void
    {
        if (self::$config !== null) {
            return;
        }

        $file = dirname(__DIR__) . '/config/config.php';

        if (!file_exists($file)) {
            throw new RuntimeException("Arquivo de configuração não encontrado: {$file}");
        }

        self::$config = require $file;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();

        $value = self::$config;

        foreach (explode('.', $key) as $segment) {

            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public static function all(): array
    {
        self::load();

        return self::$config;
    }
}
