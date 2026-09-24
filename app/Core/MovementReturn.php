<?php

declare(strict_types=1);

namespace App\Core;

final class MovementReturn
{
    public static function path(mixed $value): string
    {
        if (!is_string($value) || !str_starts_with($value, '/movimentacoes?')) {
            return '/movimentacoes';
        }
        parse_str(explode('#', substr($value, strlen('/movimentacoes?')), 2)[0], $query);
        $query = array_filter(
            array_intersect_key($query, array_flip(['q', 'status', 'tipo', 'conta_id', 'cartao_id', 'grupo_id', 'subgrupo_id', 'meio_pagamento', 'data_inicio', 'data_fim', 'pagina', 'lixeira'])),
            fn($item) => is_string($item) && strlen($item) <= 200
        );
        return '/movimentacoes' . ($query ? '?' . http_build_query($query) : '');
    }
}
