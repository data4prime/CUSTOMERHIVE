<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Formato di numeri, importi e percentuali secondo la preferenza dell'utente
 * (cms_users.decimal_separator: ',' = italiano 1.234,56 - default; '.' =
 * inglese 1,234.56) e valute selezionabili per i campi importo.
 *
 * Il DB e i valori inviati al server restano sempre in formato "1234.56":
 * questo helper serve solo per MOSTRARE (lista, dettaglio, campi del form) e
 * per rileggere ("parse") quello che l'utente digita nei campi importo.
 */
class NumberFormat
{
    const DEFAULT_SEPARATOR = ',';

    /** Valute proposte nei campi importo e nei formati di lista: codice => simbolo. */
    const CURRENCIES = [
        'EUR' => '€', 'USD' => '$', 'GBP' => '£', 'CHF' => 'CHF', 'JPY' => '¥', 'CNY' => 'CN¥',
        'CAD' => 'C$', 'AUD' => 'A$', 'NZD' => 'NZ$', 'SEK' => 'kr', 'NOK' => 'kr', 'DKK' => 'kr',
        'PLN' => 'zł', 'CZK' => 'Kč', 'HUF' => 'Ft', 'RON' => 'lei', 'BGN' => 'лв', 'TRY' => '₺',
        'RUB' => '₽', 'UAH' => '₴', 'INR' => '₹', 'KRW' => '₩', 'BRL' => 'R$', 'MXN' => 'MX$',
        'ZAR' => 'R', 'AED' => 'AED', 'SAR' => 'SAR', 'ILS' => '₪',
    ];

    private static $separator = null;

    /** Separatore decimale dell'utente loggato (',' se non impostato o colonna assente). */
    public static function decimalSeparator(): string
    {
        if (self::$separator !== null) {
            return self::$separator;
        }
        $value = self::DEFAULT_SEPARATOR;
        try {
            $id = Session::get('admin_id');
            if ($id) {
                $stored = DB::table(config('crudbooster.USER_TABLE', 'cms_users'))->where('id', $id)->value('decimal_separator');
                if ($stored === '.' || $stored === ',') {
                    $value = $stored;
                }
            }
        } catch (\Throwable $e) {
            // colonna non ancora migrata: resta il default italiano
        }

        return self::$separator = $value;
    }

    public static function thousandsSeparator(): string
    {
        return self::decimalSeparator() === ',' ? '.' : ',';
    }

    /** Dimentica il valore in cache (dopo il salvataggio della preferenza). */
    public static function flush(): void
    {
        self::$separator = null;
    }

    /** Valute per le select: codice => "€ - EUR". */
    public static function currencyOptions(): array
    {
        $out = [];
        foreach (self::CURRENCIES as $code => $symbol) {
            $out[$code] = $symbol === $code ? $code : $symbol . ' (' . $code . ')';
        }

        return $out;
    }

    /** Simbolo di una valuta; '' per codice vuoto o sconosciuto. */
    public static function currencySymbol(?string $code): string
    {
        return self::CURRENCIES[strtoupper((string) $code)] ?? '';
    }

    /**
     * Numero formattato per l'utente. Un valore non numerico si restituisce
     * invariato. $group = false toglie il separatore delle migliaia.
     */
    public static function format($value, int $decimals = 0, bool $group = true): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_string($value)) {
            $value = trim($value);
        }
        if (!is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, max(0, min(8, $decimals)), self::decimalSeparator(), $group ? self::thousandsSeparator() : '');
    }

    /**
     * Numero senza imporre decimali: tiene quelli presenti nel valore (senza
     * zeri finali inutili dei DECIMAL), solo con il separatore dell'utente.
     * Per i campi "numero" e "percentuale" senza decimali configurati.
     */
    public static function formatNatural($value): string
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $value === null ? '' : (string) $value;
        }
        $s = (string) $value;
        if (strpos($s, '.') === false) {
            return $s;
        }

        return str_replace('.', self::decimalSeparator(), rtrim(rtrim($s, '0'), '.'));
    }

    /** Importo con simbolo valuta ("€ 1.234,50"); senza valuta solo il numero. */
    public static function money($value, int $decimals, ?string $currency): string
    {
        $n = self::format($value, $decimals);
        $symbol = self::currencySymbol($currency);

        return ($n === '' || $symbol === '' || !is_numeric($value)) ? $n : $symbol . ' ' . $n;
    }

    /** Valore per un input che riparte da un numero del DB: "1234.5" -> "1234.50". */
    public static function toPlain($value, int $decimals): string
    {
        return is_numeric($value) ? number_format((float) $value, max(0, min(8, $decimals)), '.', '') : (string) $value;
    }

    /**
     * Rilegge un numero scritto dall'utente nel suo formato ("1.234,56") e lo
     * riporta a "1234.56". Va bene anche un valore gia' in formato DB senza
     * separatori delle migliaia.
     */
    public static function parse($input): string
    {
        $s = trim((string) $input);
        if ($s === '') {
            return '';
        }
        $dec = self::decimalSeparator();
        $s = preg_replace('/[^\d\-' . preg_quote($dec, '/') . ']/', '', $s);
        $s = str_replace($dec, '.', $s);

        return $s;
    }
}
