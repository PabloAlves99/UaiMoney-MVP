<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(
        string $view,
        array $data = [],
        ?string $layout = null
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

        /*
         * Se não houver layout,
         * renderizamos a View normalmente.
         */
        if ($layout === null) {
            require $viewPath;

            return;
        }

        /*
         * Captura o HTML produzido pela View.
         */
        ob_start();

        require $viewPath;

        $content = ob_get_clean();


        /*
         * Localiza o layout.
         */
        $layoutPath = dirname(__DIR__)
            . '/Views/'
            . $layout
            . '.php';

        if (!is_file($layoutPath)) {
            throw new RuntimeException(
                "Layout não encontrado: {$layout}"
            );
        }

        require $layoutPath;
    }
}