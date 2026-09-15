<?php

declare(strict_types=1);

namespace App\Core;

use DomainException;

final class Money
{
    public static function toCents(
        string $value
    ): int {
        $value = trim($value);

        if ($value === '') {
            throw new DomainException(
                'Informe um valor válido.'
            );
        }

        /*
         * Aceita:
         *
         * 1500
         * 1500.50
         * 1500,50
         */

        $value = str_replace(
            ',',
            '.',
            $value
        );

        if (
            !preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $value
            )
        ) {
            throw new DomainException(
                'Informe um valor monetário válido.'
            );
        }

        [$inteiro, $decimal] = array_pad(
            explode('.', $value, 2),
            2,
            '0'
        );

        $decimal = str_pad(
            $decimal,
            2,
            '0'
        );

        return ((int) $inteiro * 100)
            + (int) $decimal;
    }


    public static function format(
        int $centavos
    ): string {
        return 'R$ '
            . number_format(
                $centavos / 100,
                2,
                ',',
                '.'
            );
    }
}