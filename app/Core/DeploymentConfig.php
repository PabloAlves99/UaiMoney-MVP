<?php
declare(strict_types=1);
namespace App\Core;

use RuntimeException;

final class DeploymentConfig
{
    /** Credentials live beside public_html, never inside it. Explicit paths support other layouts. */
    public static function path(string $projectRoot): ?string
    {
        $explicit = getenv('UAIMONEY_CONFIG_FILE');
        if ($explicit !== false && $explicit !== '') return $explicit;
        for ($directory = $projectRoot; dirname($directory) !== $directory; $directory = dirname($directory)) {
            if (strtolower(basename($directory)) === 'public_html') {
                return dirname($directory) . '/private/uaimoney.php';
            }
        }
        return null;
    }

    public static function read(string $projectRoot): array
    {
        $path = self::path($projectRoot);
        if ($path === null) return [];
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Configuração privada de produção ausente ou sem permissão de leitura. Consulte docs/hostinger-update.md.');
        }
        $config = require $path;
        if (!is_array($config) || !is_array($config['app'] ?? null) || !is_array($config['mail'] ?? null)) {
            throw new RuntimeException('Configuração privada inválida: são necessárias as seções app e mail.');
        }
        return $config;
    }
}
