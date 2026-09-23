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

        if (preg_match('/^\\d{1,3}(?:\\.\\d{3})+,\\d{1,2}$/', $value)) {
            $value = str_replace('.', '', $value);
        }
        $value = str_replace(',', '.', $value);
        if (!preg_match('/^\\d{1,10}(?:\\.\\d{1,2})?$/', $value)) {
            throw new DomainException('Informe um valor válido, como 1234,56 ou 1.234,56.');
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
        $negativo = $centavos < 0;

        $valor = number_format(
            abs($centavos) / 100,
            2,
            ',',
            '.'
        );

        return ($negativo ? '- ' : '')
            . 'R$ '
            . $valor;
    }

    public static function toSignedCents(string $value): int
    {
        $value = trim($value);
        $negative = str_starts_with($value, '-');
        return self::toCents($negative ? substr($value, 1) : $value) * ($negative ? -1 : 1);
    }
}
