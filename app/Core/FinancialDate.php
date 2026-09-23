<?php
declare(strict_types=1);
namespace App\Core;

use DateTimeImmutable;
use DomainException;

final class FinancialDate
{
    public static function date(string $value): string
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value || $value < '1900-01-01' || $value > '2199-12-31') {
            throw new DomainException('Informe uma data válida entre 1900 e 2199.');
        }
        return $value;
    }

    public static function month(string $value): string
    {
        self::date($value . '-01');
        return $value;
    }

    public static function day(string $month, int $day): string
    {
        self::month($month);
        return $month . '-' . sprintf('%02d', min($day, (int) (new DateTimeImmutable($month . '-01'))->format('t')));
    }

    public static function shift(string $month, int $count): string
    {
        self::month($month);
        return (new DateTimeImmutable($month . '-01'))->modify("$count months")->format('Y-m');
    }

    public static function past(string $date): string
    {
        self::date($date);
        if ($date > date('Y-m-d')) {
            throw new DomainException('Uma operação realizada não pode ter data futura.');
        }
        return $date;
    }
}
