<?php
namespace App\Helpers;

/**
 * Regole "in stile NIST 800-63B": niente requisiti di composizione
 * (maiuscola/minuscola/numero/simbolo obbligatori), ma blocco di password
 * comuni/prevedibili, sequenze/ripetizioni e parole legate al contesto
 * dell'utente (email, nome). La lunghezza minima/massima resta nel campo
 * 'validation' del form (min/max), qui c'e' solo il controllo "prevedibilita'".
 */
class PasswordPolicy
{
    private static ?array $commonPasswords = null;

    public static function isCommon(string $password): bool
    {
        return in_array(strtolower($password), self::commonPasswords(), true);
    }

    /**
     * Rileva run di almeno $runLength caratteri identici (aaaaaa) o in
     * sequenza crescente/decrescente di codice ASCII (abcdef, 123456,
     * fedcba, 654321).
     */
    public static function hasSequentialOrRepeatedChars(string $password, int $runLength = 6): bool
    {
        $chars = str_split(strtolower($password));
        $repeatRun = 1;
        $ascRun = 1;
        $descRun = 1;

        for ($i = 1; $i < count($chars); $i++) {
            $diff = ord($chars[$i]) - ord($chars[$i - 1]);

            $repeatRun = ($diff === 0) ? $repeatRun + 1 : 1;
            $ascRun = ($diff === 1) ? $ascRun + 1 : 1;
            $descRun = ($diff === -1) ? $descRun + 1 : 1;

            if (max($repeatRun, $ascRun, $descRun) >= $runLength) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rifiuta la password se contiene (come sottostringa) l'email o il nome
     * dell'utente, cosi' come consigliato da NIST per le "context-specific
     * words". I token troppo corti (<4 caratteri) sono ignorati per evitare
     * falsi positivi su nomi/cognomi brevi.
     */
    public static function containsContextWord(string $password, array $contextValues): bool
    {
        $password = strtolower($password);

        foreach ($contextValues as $value) {
            if (!$value) {
                continue;
            }

            foreach (preg_split('/[\s@.]+/', strtolower($value)) as $token) {
                if (strlen($token) >= 4 && str_contains($password, $token)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function commonPasswords(): array
    {
        if (self::$commonPasswords === null) {
            $path = resource_path('security/common-passwords.txt');
            $lines = is_file($path) ? file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
            self::$commonPasswords = array_map('strtolower', $lines);
        }

        return self::$commonPasswords;
    }
}
