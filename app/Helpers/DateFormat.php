<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Formato di visualizzazione delle date secondo la preferenza dell'utente
 * (cms_users.date_format, profilo > Preferenze). Vale per lista e dettaglio;
 * i valori nel DB e nei campi del form non cambiano.
 */
class DateFormat
{
    const DEFAULT_FORMAT = 'd/m/Y';

    /** I formati proposti (pattern PHP date()). */
    const FORMATS = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y', 'd.m.Y'];

    private static $pattern = null;

    public static function pattern(): string
    {
        if (self::$pattern !== null) {
            return self::$pattern;
        }
        $value = self::DEFAULT_FORMAT;
        try {
            $id = Session::get('admin_id');
            if ($id) {
                $stored = DB::table(config('crudbooster.USER_TABLE', 'cms_users'))->where('id', $id)->value('date_format');
                if (in_array($stored, self::FORMATS, true)) {
                    $value = $stored;
                }
            }
        } catch (\Throwable $e) {
            // colonna non ancora migrata: resta il default
        }

        return self::$pattern = $value;
    }

    public static function flush(): void
    {
        self::$pattern = null;
    }

    /** Data di esempio per le opzioni della preferenza (giorno > 12: non ambigua). */
    public static function sample(string $pattern): string
    {
        return Carbon::create(2026, 12, 25, 14, 30)->format($pattern);
    }

    /** Solo la data; un valore non interpretabile si restituisce invariato. */
    public static function date($value): string
    {
        return self::render($value, self::pattern());
    }

    /** Data e ora (HH:MM). */
    public static function dateTime($value): string
    {
        return self::render($value, self::pattern() . ' H:i');
    }

    private static function render($value, string $format): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }
        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
