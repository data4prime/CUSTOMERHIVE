<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Settings piu' comprensibili (intervento 237): i tre colori del gruppo
 * Login Register Style passano dal tipo "text" al nuovo tipo "color" e la
 * dimensione carta predefinita da "text" a "select" con i formati disponibili.
 *
 * Il valore salvato non cambia: una riga viene convertita solo se il
 * contenuto attuale e' gia' compatibile col nuovo tipo (esadecimale a 6 cifre
 * o vuoto; formato carta presente nell'elenco). Le altre restano "text".
 * Idempotente: tocca solo righe ancora di tipo "text".
 */
class SettingsColorAndPaperSizeTypes extends Migration
{
    private const PAPER = ['Letter', 'Legal', 'Ledger', 'A0', 'A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8',
        'B0', 'B1', 'B2', 'B3', 'B4', 'B5', 'B6', 'B7', 'B8', 'B9', 'B10'];

    private const COLORS = ['login_background_color', 'login_font_color', 'button_color'];

    public function up()
    {
        foreach (self::COLORS as $name) {
            $row = DB::table('cms_settings')->where('name', $name)->where('content_input_type', 'text')->first();
            if (! $row) {
                continue;
            }
            $content = trim((string) $row->content);
            if ($content === '' || preg_match('/^#[0-9a-fA-F]{6}$/', $content)) {
                DB::table('cms_settings')->where('id', $row->id)->update(['content_input_type' => 'color']);
                Cache::forget('setting_' . $name);
            }
        }

        $paper = DB::table('cms_settings')->where('name', 'default_paper_size')->where('content_input_type', 'text')->first();
        if ($paper) {
            $content = trim((string) $paper->content);
            if ($content === '' || in_array($content, self::PAPER, true)) {
                DB::table('cms_settings')->where('id', $paper->id)->update([
                    'content_input_type' => 'select',
                    'dataenum' => implode(',', self::PAPER),
                    'helper' => null,
                ]);
                Cache::forget('setting_default_paper_size');
            }
        }
    }

    public function down()
    {
        DB::table('cms_settings')->whereIn('name', self::COLORS)->where('content_input_type', 'color')
            ->update(['content_input_type' => 'text']);

        DB::table('cms_settings')->where('name', 'default_paper_size')->where('content_input_type', 'select')
            ->update(['content_input_type' => 'text', 'dataenum' => null, 'helper' => 'Paper size, ex : A4, Legal, etc']);

        foreach (array_merge(self::COLORS, ['default_paper_size']) as $name) {
            Cache::forget('setting_' . $name);
        }
    }
}
