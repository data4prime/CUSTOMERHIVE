<?php

namespace Tests\Feature;

use App\Models\QlikItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test di caratterizzazione (batch 0 del piano 317) per
 * QlikItem::enablePublicAccess()/disablePublicAccess()/isPublic():
 * token casuale (bin2hex di 20 byte = 40 caratteri esadecimali) e
 * proxy_enabled_at valorizzati/azzerati; ritorno true quando lo stato cambia,
 * false quando l'item e' gia' nello stato richiesto (e in quel caso non si
 * tocca nulla, token compreso).
 */
class QlikItemPublicAccessTest extends TestCase
{
    use RefreshDatabase;

    private function seedItem(array $overrides = []): QlikItem
    {
        $id = DB::table('qlik_items')->insertGetId(array_merge([
            'title' => 'Item Pubblico Test',
            'created_at' => now(),
        ], $overrides));

        return QlikItem::find($id);
    }

    public function test_enable_su_item_non_pubblico_imposta_token_e_date_e_ritorna_true(): void
    {
        $item = $this->seedItem();
        $this->assertFalse($item->isPublic());

        $this->assertTrue($item->enablePublicAccess());

        $row = DB::table('qlik_items')->where('id', $item->id)->first();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $row->proxy_token);
        $this->assertNotNull($row->proxy_enabled_at);
        $this->assertNotNull($row->modified_at);
        $this->assertTrue($item->isPublic());
    }

    public function test_enable_su_item_gia_pubblico_ritorna_false_e_non_cambia_il_token(): void
    {
        $item = $this->seedItem();
        $item->enablePublicAccess();
        $tokenBefore = DB::table('qlik_items')->where('id', $item->id)->value('proxy_token');

        $this->assertFalse($item->enablePublicAccess());

        $this->assertSame($tokenBefore, DB::table('qlik_items')->where('id', $item->id)->value('proxy_token'));
    }

    public function test_disable_su_item_pubblico_azzera_token_e_data_e_ritorna_true(): void
    {
        $item = $this->seedItem();
        $item->enablePublicAccess();

        $this->assertTrue($item->disablePublicAccess());

        $row = DB::table('qlik_items')->where('id', $item->id)->first();
        $this->assertNull($row->proxy_token);
        $this->assertNull($row->proxy_enabled_at);
        $this->assertNotNull($row->modified_at);
        $this->assertFalse($item->isPublic());
    }

    public function test_disable_su_item_non_pubblico_ritorna_false(): void
    {
        $item = $this->seedItem();

        $this->assertFalse($item->disablePublicAccess());

        $this->assertNull(DB::table('qlik_items')->where('id', $item->id)->value('proxy_token'));
    }

    public function test_riabilitare_dopo_disabilitare_genera_un_token_diverso(): void
    {
        $item = $this->seedItem();
        $item->enablePublicAccess();
        $firstToken = $item->proxy_token;
        $item->disablePublicAccess();

        $this->assertTrue($item->enablePublicAccess());

        $this->assertNotSame($firstToken, $item->proxy_token);
    }
}
