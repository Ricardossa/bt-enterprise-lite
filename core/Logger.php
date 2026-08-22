<?php

declare(strict_types=1);

namespace BTQueue\Core;

class Logger
{
    private static function file(string $channel = 'app'): string
    {
        $path = Config::get('logs.path');

        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }

        // Canais especializados: sync, license, update, error, app
        return $path . '/' . $channel . '-' . date('Y-m-d') . '.log';
    }

    public static function write(
        string $level,
        string $message,
        array $context = [],
        string $channel = 'app'
    ): void {

        $line = sprintf(
            "[%s] [%s] %s",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );

        if (!empty($context)) {
            $line .= ' | ' . json_encode(
                $context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        file_put_contents(
            self::file($channel),
            $line . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    public static function channel(string $channel, string $level, string $message, array $context = []): void
    {
        self::write($level, $message, $context, $channel);
    }

    public static function info(string $message, array $context = [], string $channel = 'app'): void
    {
        self::write('INFO', $message, $context, $channel);
    }

    public static function warning(string $message, array $context = [], string $channel = 'app'): void
    {
        self::write('WARNING', $message, $context, $channel);
    }

    public static function error(string $message, array $context = [], string $channel = 'app'): void
    {
        self::write('ERROR', $message, $context, $channel);
    }

    public static function debug(string $message, array $context = [], string $channel = 'app'): void
    {
        if (Config::get('app.debug', false)) {
            self::write('DEBUG', $message, $context, $channel);
        }
    }
}
