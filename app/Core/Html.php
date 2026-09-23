<?php
declare(strict_types=1);
namespace App\Core;

final class Html
{
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''),ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
    }

    public static function date(?string $value): string
    {
        return $value ? self::escape(implode('/',array_reverse(explode('-',$value)))) : '—';
    }

    public static function fields(string $token): string
    {
        return '<input type="hidden" name="_token" value="'.self::escape($token).'"><input type="hidden" name="_operation" value="'.bin2hex(random_bytes(16)).'">';
    }
}
