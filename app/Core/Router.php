<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];


    public function __construct(
        private readonly string $basePath = ''
    ) {}


    public function get(
        string $path,
        callable $handler
    ): void {
        $this->addRoute(
            'GET',
            $path,
            $handler
        );
    }


    public function post(
        string $path,
        callable $handler
    ): void {
        $this->addRoute(
            'POST',
            $path,
            $handler
        );
    }


    private function addRoute(
        string $method,
        string $path,
        callable $handler
    ): void {
        $path = $this->normalizePath(
            $path
        );

        [$regex, $parameterNames] =
            $this->compileRoute($path);


        $this->routes[] = [

            'method' => strtoupper($method),

            'path' => $path,

            'regex' => $regex,

            'parameters' => $parameterNames,

            'handler' => $handler

        ];
    }


    public function dispatch(
        string $method,
        string $uri
    ): void {
        $method = strtoupper($method);


        /*
         * Pega somente o caminho da URL.
         *
         * Exemplo:
         *
         * /categorias/15?teste=1
         *
         * vira:
         *
         * /categorias/15
         */

        $path = parse_url(
            $uri,
            PHP_URL_PATH
        ) ?: '/';


        /*
         * Remove o basePath do ambiente local.
         *
         * /uaimoney-mvp/public/categorias/15
         *
         * vira:
         *
         * /categorias/15
         */

        if (
            $this->basePath !== '' &&
            (
                $path === $this->basePath ||
                str_starts_with(
                    $path,
                    $this->basePath . '/'
                )
            )
        ) {
            $path = substr(
                $path,
                strlen($this->basePath)
            );
        }


        $path = $this->normalizePath(
            $path
        );


        /*
         * Guarda métodos permitidos caso o caminho
         * exista, mas o método HTTP esteja errado.
         */

        $allowedMethods = [];


        foreach ($this->routes as $route) {

            $matches = [];


            /*
             * Verifica se a URL corresponde
             * ao padrão da rota.
             */

            if (
                preg_match(
                    $route['regex'],
                    $path,
                    $matches
                ) !== 1
            ) {
                continue;
            }


            /*
             * O caminho existe, mas pode ser
             * GET enquanto recebemos POST,
             * por exemplo.
             */

            if ($route['method'] !== $method) {

                $allowedMethods[] =
                    $route['method'];

                continue;
            }


            /*
             * O primeiro item de $matches é
             * sempre a URL completa.
             *
             * Não queremos passá-la ao Controller.
             */

            array_shift($matches);


            /*
             * Agora $matches contém somente
             * os parâmetros da URL.
             */

            call_user_func_array(
                $route['handler'],
                array_values($matches)
            );

            return;
        }


        /*
         * A URL existe, mas não para esse
         * método HTTP.
         */

        if ($allowedMethods !== []) {

            $allowedMethods = array_unique(
                $allowedMethods
            );

            header(
                'Allow: '
                    . implode(
                        ', ',
                        $allowedMethods
                    )
            );

            http_response_code(405);

            echo '405 - Método não permitido';

            return;
        }


        /*
         * Nenhuma rota encontrada.
         */

        http_response_code(404);

        echo '404 - Página não encontrada';
    }


    private function compileRoute(
        string $path
    ): array {
        /*
         * Caso especial da Home.
         */

        if ($path === '/') {
            return [
                '#^/$#',
                []
            ];
        }


        $segments = explode(
            '/',
            trim($path, '/')
        );

        $regex = '';

        $parameterNames = [];


        foreach ($segments as $segment) {

            /*
             * Verifica se o segmento é
             * um parâmetro:
             *
             * {id}
             * {conta}
             * {usuario}
             */

            if (
                preg_match(
                    '/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/',
                    $segment,
                    $matches
                ) === 1
            ) {

                $parameterNames[] =
                    $matches[1];


                /*
                 * Aceita qualquer coisa,
                 * exceto uma nova "/".
                 */

                $regex .= '/([^/]+)';

                continue;
            }


            /*
             * Segmento fixo.
             *
             * Exemplo:
             *
             * categorias
             */

            $regex .= '/'
                . preg_quote(
                    $segment,
                    '#'
                );
        }


        return [
            '#^' . $regex . '$#',
            $parameterNames
        ];
    }


    private function normalizePath(
        string $path
    ): string {
        $path = trim($path);


        if (
            $path === '' ||
            $path === '/'
        ) {
            return '/';
        }


        return '/'
            . trim($path, '/');
    }
}
