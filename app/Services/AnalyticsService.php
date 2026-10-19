<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\FinancialDate as Dates;
use DateTimeImmutable;
use DomainException;

final class AnalyticsService
{
    public static function selections(array $input, array $categories): array
    {
        $available = [];
        foreach ($categories as $category) {
            $type = $category['tipo'];
            $available[$type . '_grupos'][(int)$category['grupo_id']] = true;
            $available[$type . '_subgrupos'][(int)$category['id']] = true;
        }
        $active = $values = $filters = $query = [];
        foreach (['receita_grupos', 'receita_subgrupos', 'despesa_grupos', 'despesa_subgrupos'] as $key) {
            $active[$key] = ($input[$key . '_ativo'] ?? null) === '1';
            $submitted = $input[$key] ?? [];
            if (!is_array($submitted)) $submitted = [];
            $values[$key] = [];
            if (!$active[$key]) continue;
            foreach ($submitted as $id) {
                if (!is_string($id) || !ctype_digit($id) || !isset($available[$key][(int)$id])) continue;
                $values[$key][(int)$id] = (int)$id;
            }
            $values[$key] = array_values($values[$key]);
            $filters[$key] = $values[$key];
            $query[$key . '_ativo'] = '1';
            if ($values[$key]) $query[$key] = $values[$key];
        }
        return compact('active', 'values', 'filters', 'query');
    }

    public static function period(array $input, string $today): array
    {
        $month = substr($today, 0, 7);
        $preset = (string)($input['periodo'] ?? (isset($input['inicio']) || isset($input['fim']) || isset($input['mes']) ? 'personalizado' : 'mes'));
        $start = $month . '-01';
        $end = $today;
        try {
            if ($preset === 'anterior') {
                $start = Dates::shift($month, -1) . '-01';
                $end = Dates::day(substr($start, 0, 7), 31);
            } elseif ($preset === 'semestre') {
                $start = Dates::shift($month, -5) . '-01';
            } elseif ($preset === 'personalizado') {
                $selectedMonth = Dates::month((string)($input['mes'] ?? $month));
                $start = Dates::date((string)($input['inicio'] ?? $selectedMonth . '-01'));
                $end = Dates::date((string)($input['fim'] ?? ($selectedMonth === $month ? $today : Dates::day($selectedMonth, 31))));
            } else {
                $preset = 'mes';
            }
        } catch (DomainException) {
            $preset = 'mes';
            $start = $month . '-01';
            $end = $today;
        }
        if ($start > $end) [$start, $end] = [$end, $start];
        $days = (new DateTimeImmutable($start))->diff(new DateTimeImmutable($end))->days + 1;
        $singleMonth = substr($start, 0, 7) === substr($end, 0, 7);
        if ($singleMonth && substr($start, 8) === '01') {
            $previousMonth = (new DateTimeImmutable($start))->modify('-1 month');
            $previousStart = $previousMonth->format('Y-m-d');
            $lastDay = (int)$previousMonth->format('t');
            $day = $end === Dates::day(substr($end, 0, 7), 31) ? $lastDay : min((int)substr($end, 8), $lastDay);
            $previousEnd = $previousMonth->format('Y-m-') . sprintf('%02d', $day);
        } else {
            $previousEnd = (new DateTimeImmutable($start))->modify('-1 day')->format('Y-m-d');
            $previousStart = (new DateTimeImmutable($start))->modify("-$days days")->format('Y-m-d');
        }
        $seriesStart = min($start, max('1900-01-01', Dates::shift(substr($end, 0, 7), -5) . '-01'));
        $partial = $end === $today && $end !== Dates::day($month, 31);
        return compact('preset', 'start', 'end', 'previousStart', 'previousEnd', 'seriesStart', 'singleMonth', 'partial', 'days');
    }
}
