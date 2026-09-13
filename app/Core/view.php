<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(
        string $view,
        array $data = []
    ): void {
        $viewPath = dirname(__DIR__)
            . '/Views/'
            . $view
            . '.php';

        if (!is_file($viewPath)) {
            throw new RuntimeException(
                "View não encontrada: {$view}"
            );
        }

        extract(
            $data,
            EXTR_SKIP
        );

        require $viewPath;
    }
}