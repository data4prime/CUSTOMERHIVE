<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Test di caratterizzazione per il modulo Settings (SettingsController).
 * Modulo ibrido: meta' motore CBController standard su cms_settings (righe
 * singole: add/edit/delete), meta' due schermate custom scritte a mano che
 * sono il vero front-end ("Impostazioni" per gruppo): getShow() (mostra i
 * setting di un gruppo) + postSaveSetting() (li salva tutti insieme).
 *
 * Prima di scrivere questi test sono stati corretti 3 bug reali trovati in
 * analisi:
 * - CRUDBooster::valid() (usata da postSaveSetting() per validare gli
 *   upload) chiamava exit() dopo aver inviato la response, invece di
 *   tornarla - stessa classe di problema gia' risolta per
 *   CRUDBooster::redirect()/CBController::validation(), ma mai applicata
 *   qui. Bloccava la scrivibilita' del test "upload non valido rifiutato"
 *   (un exit() in PHPUnit termina l'intero processo, non solo il test).
 * - cms_settings non ha 'deleted_at': CBController::getDelete() fa una
 *   DELETE fisica. SettingsController non aveva hook_before_delete/
 *   hook_after_delete: cancellare una riga NON invalidava la cache
 *   (CRUDBooster::getSetting() usa Cache::forever(), quindi il vecchio
 *   valore restava servito indefinitamente) e lasciava orfano l'eventuale
 *   file caricato. Aggiunto hook_before_delete() che riusa la stessa logica
 *   gia' corretta in getDeleteFileSetting().
 *
 * setUp() registra il modulo "settings" (gia' incluso di default in
 * SeedsCmsData::registerAdminModules(), referenziato dal sidebar admin
 * condiviso).
 */
class SettingsCrudTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminModules();
    }

    /**
     * Le due sole tests che toccano davvero il filesystem (getDeleteFileSetting()
     * e la cancellazione riga leggono/cancellano un file via public_path(),
     * bypassando lo Storage facade - Storage::fake() non le intercetta)
     * scrivono in questa sottocartella dedicata, ripulita ad ogni test.
     */
    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('storage/uploads/phpunit-settings-test'));

        parent::tearDown();
    }

    private function createFakeFileOnDisk(string $relativePath): void
    {
        $absolute = public_path(ltrim($relativePath, '/'));
        File::ensureDirectoryExists(dirname($absolute));
        File::put($absolute, 'contenuto fittizio per il test');
    }

    // ---------------------------------------------------------------
    // CRUD standard delle righe (cbInit() / motore CBController)
    // ---------------------------------------------------------------

    public function test_lista_mostra_le_righe_di_setting(): void
    {
        $this->actingAsSuperadmin();
        $this->seedSetting(['name' => 'voce_da_trovare_in_lista']);

        $response = $this->get('http://localhost/admin/settings');

        $response->assertStatus(200);
        // col[] 'name' usa callback_php ucwords(str_replace('_',' ',$row->name)).
        $response->assertSee('Voce Da Trovare In Lista');
    }

    public function test_creazione_setting_genera_name_da_slug_della_label(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->post('http://localhost/admin/settings/add-save', [
            'group_setting' => 'General Setting',
            'label' => 'Colore Tema Principale',
            'content_input_type' => 'text',
            'helper' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');
        $this->assertDatabaseHas('cms_settings', [
            'name' => 'colore_tema_principale',
            'label' => 'Colore Tema Principale',
        ]);
    }

    public function test_creazione_setting_con_name_duplicato_viene_bloccata(): void
    {
        $this->actingAsSuperadmin();
        $this->seedSetting(['name' => 'tema_colore', 'label' => 'Tema Colore', 'group_setting' => 'Gruppo A']);

        $response = $this->post('http://localhost/admin/settings/add-save', [
            'group_setting' => 'Gruppo B',
            'label' => 'Tema Colore',
            'content_input_type' => 'text',
            'helper' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas(
            'message',
            'A setting named "tema_colore" already exists. Please choose a different label.'
        );
        $this->assertSame(1, DB::table('cms_settings')->where('name', 'tema_colore')->count());
    }

    public function test_modifica_setting_invalida_la_cache(): void
    {
        $this->actingAsSuperadmin();
        $setting = $this->seedSetting(['name' => 'cache_test']);
        Cache::put('setting_cache_test', 'valore-in-cache-prima-della-modifica');

        $response = $this->post("http://localhost/admin/settings/edit-save/{$setting['id']}", [
            'group_setting' => 'General Setting',
            'label' => 'Cache Test Modificato',
            'content_input_type' => 'text',
            'helper' => '',
        ]);

        $response->assertStatus(302);
        $this->assertFalse(Cache::has('setting_cache_test'));
    }

    public function test_cancellazione_riga_invalida_la_cache(): void
    {
        $this->actingAsSuperadmin();
        $setting = $this->seedSetting(['name' => 'del_cache_test', 'content' => 'valore reale']);
        Cache::put('setting_del_cache_test', 'valore-vecchio-in-cache');

        $response = $this->get("http://localhost/admin/settings/delete/{$setting['id']}");

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');
        $this->assertFalse(Cache::has('setting_del_cache_test'));
        $this->assertNull(\CRUDBooster::getSetting('del_cache_test'));
    }

    public function test_cancellazione_riga_con_file_associato_rimuove_il_file_fisico(): void
    {
        $this->actingAsSuperadmin();
        $relativePath = '/storage/uploads/phpunit-settings-test/logo-riga.png';
        $this->createFakeFileOnDisk($relativePath);
        $setting = $this->seedSetting([
            'name' => 'logo_con_file',
            'content_input_type' => 'upload_image',
            'content' => $relativePath,
        ]);
        $this->assertFileExists(public_path($relativePath), 'Precondizione non valida: il file di test non e\' stato creato.');

        $response = $this->get("http://localhost/admin/settings/delete/{$setting['id']}");

        $response->assertStatus(302);
        $this->assertFileDoesNotExist(public_path($relativePath));
    }

    public function test_cancellazione_riga_senza_content_non_va_in_errore(): void
    {
        $this->actingAsSuperadmin();
        $setting = $this->seedSetting(['name' => 'senza_content', 'content' => null]);

        $response = $this->get("http://localhost/admin/settings/delete/{$setting['id']}");

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');
        $this->assertDatabaseMissing('cms_settings', ['id' => $setting['id']]);
    }

    // ---------------------------------------------------------------
    // getShow() - vista raggruppata (front-end reale del modulo)
    // ---------------------------------------------------------------

    public function test_getshow_nega_accesso_a_non_superadmin(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['settings']);

        $response = $this->get('http://localhost/admin/settings/show?group=' . urlencode('General Setting'));

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
    }

    public function test_getshow_mostra_i_setting_del_gruppo_richiesto(): void
    {
        $this->actingAsSuperadmin();
        $this->seedSetting(['name' => 'a', 'label' => 'Impostazione A', 'group_setting' => 'Gruppo Uno']);
        $this->seedSetting(['name' => 'b', 'label' => 'Impostazione B', 'group_setting' => 'Gruppo Due']);

        $response = $this->get('http://localhost/admin/settings/show?group=' . urlencode('Gruppo Uno'));

        $response->assertStatus(200);
        $response->assertSee('Impostazione A');
        $response->assertDontSee('Impostazione B');
    }

    public function test_getshow_ripara_le_label_vuote(): void
    {
        $this->actingAsSuperadmin();
        $this->seedSetting(['name' => 'senza_label', 'label' => '', 'group_setting' => 'Gruppo Repair']);

        $response = $this->get('http://localhost/admin/settings/show?group=' . urlencode('Gruppo Repair'));

        $response->assertStatus(200);
        $this->assertDatabaseHas('cms_settings', ['name' => 'senza_label', 'label' => 'Senza Label']);
    }

    // ---------------------------------------------------------------
    // postSaveSetting() - salvataggio massivo del gruppo
    // ---------------------------------------------------------------

    public function test_postsavesetting_nega_accesso_a_non_superadmin(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['settings']);
        $setting = $this->seedSetting(['name' => 'protetto', 'group_setting' => 'Gruppo Protetto', 'content' => 'originale']);

        $response = $this->post('http://localhost/admin/settings/save-setting', [
            'group_setting' => 'Gruppo Protetto',
            'protetto' => 'tentativo di scrittura',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
        $this->assertDatabaseHas('cms_settings', ['id' => $setting['id'], 'content' => 'originale']);
    }

    public function test_postsavesetting_ignora_i_campi_non_presenti_nella_request(): void
    {
        $this->actingAsSuperadmin();
        $this->seedSetting(['name' => 'campo_a', 'group_setting' => 'Gruppo Save', 'content' => 'valore a']);
        $this->seedSetting(['name' => 'campo_b', 'group_setting' => 'Gruppo Save', 'content' => 'valore b']);

        $response = $this->post('http://localhost/admin/settings/save-setting', [
            'group_setting' => 'Gruppo Save',
            'campo_a' => 'nuovo valore a',
            // 'campo_b' volutamente assente dal payload.
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('cms_settings', ['name' => 'campo_a', 'content' => 'nuovo valore a']);
        $this->assertDatabaseHas('cms_settings', ['name' => 'campo_b', 'content' => 'valore b']);
    }

    public function test_postsavesetting_permette_di_svuotare_un_campo_di_testo(): void
    {
        $this->actingAsSuperadmin();
        $this->seedSetting(['name' => 'testo_svuotabile', 'group_setting' => 'Gruppo Save2', 'content' => 'valore iniziale']);

        $response = $this->post('http://localhost/admin/settings/save-setting', [
            'group_setting' => 'Gruppo Save2',
            'testo_svuotabile' => '',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('cms_settings', ['name' => 'testo_svuotabile', 'content' => '']);
    }

    public function test_postsavesetting_password_vuota_non_sovrascrive_il_valore_esistente(): void
    {
        $this->actingAsSuperadmin();
        $this->seedSetting([
            'name' => 'password_field',
            'group_setting' => 'Gruppo Password',
            'content_input_type' => 'password',
            'content' => 'segreto-attuale',
        ]);

        $response = $this->post('http://localhost/admin/settings/save-setting', [
            'group_setting' => 'Gruppo Password',
            'password_field' => '',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('cms_settings', ['name' => 'password_field', 'content' => 'segreto-attuale']);
    }

    public function test_postsavesetting_password_valorizzata_aggiorna_il_valore(): void
    {
        $this->actingAsSuperadmin();
        $this->seedSetting([
            'name' => 'password_field2',
            'group_setting' => 'Gruppo Password',
            'content_input_type' => 'password',
            'content' => 'segreto-vecchio',
        ]);

        $response = $this->post('http://localhost/admin/settings/save-setting', [
            'group_setting' => 'Gruppo Password',
            'password_field2' => 'segreto-nuovo',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('cms_settings', ['name' => 'password_field2', 'content' => 'segreto-nuovo']);
    }

    public function test_postsavesetting_upload_image_valido_salva_path_relativo(): void
    {
        Storage::fake('local');
        $this->actingAsSuperadmin();
        $this->seedSetting([
            'name' => 'logo_img',
            'group_setting' => 'Gruppo Upload',
            'content_input_type' => 'upload_image',
            'content' => null,
        ]);

        $response = $this->post('http://localhost/admin/settings/save-setting', [
            'group_setting' => 'Gruppo Upload',
            'logo_img' => UploadedFile::fake()->image('logo.png', 100, 100)->size(50),
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');
        $row = DB::table('cms_settings')->where('name', 'logo_img')->first();
        $this->assertMatchesRegularExpression('#^/storage/uploads/\d{4}-\d{2}/[a-f0-9]{32}\.png$#', $row->content);
        Storage::disk('local')->assertExists(substr($row->content, strlen('/storage/')));
    }

    public function test_postsavesetting_upload_image_non_valido_viene_rifiutato(): void
    {
        Storage::fake('local');
        $this->actingAsSuperadmin();
        $this->seedSetting([
            'name' => 'logo_invalido',
            'group_setting' => 'Gruppo Upload2',
            'content_input_type' => 'upload_image',
            'content' => '/storage/uploads/2020-01/esistente.png',
        ]);

        $response = $this->post('http://localhost/admin/settings/save-setting', [
            'group_setting' => 'Gruppo Upload2',
            'logo_invalido' => UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf'),
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'warning');
        $this->assertDatabaseHas('cms_settings', [
            'name' => 'logo_invalido',
            'content' => '/storage/uploads/2020-01/esistente.png',
        ]);
    }

    public function test_postsavesetting_upload_file_con_estensione_non_ammessa_viene_rifiutato(): void
    {
        Storage::fake('local');
        $this->actingAsSuperadmin();
        $this->seedSetting([
            'name' => 'allegato',
            'group_setting' => 'Gruppo Upload3',
            'content_input_type' => 'upload_file',
            'content' => '/storage/uploads/2020-01/vecchio.pdf',
        ]);

        $response = $this->post('http://localhost/admin/settings/save-setting', [
            'group_setting' => 'Gruppo Upload3',
            'allegato' => UploadedFile::fake()->create('malware.exe', 10),
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'warning');
        $this->assertDatabaseHas('cms_settings', [
            'name' => 'allegato',
            'content' => '/storage/uploads/2020-01/vecchio.pdf',
        ]);
    }

    public function test_postsavesetting_upload_fallito_lato_storage_non_azzera_il_valore(): void
    {
        Storage::shouldReceive('makeDirectory')->once()->andReturn(true);
        Storage::shouldReceive('putFileAs')->once()->andReturn(false);
        $this->actingAsSuperadmin();
        $this->seedSetting([
            'name' => 'logo_fail',
            'label' => 'Logo Con Scrittura Fallita',
            'group_setting' => 'Gruppo Upload4',
            'content_input_type' => 'upload_image',
            'content' => '/storage/uploads/2020-01/mantieni.png',
        ]);

        $response = $this->post('http://localhost/admin/settings/save-setting', [
            'group_setting' => 'Gruppo Upload4',
            'logo_fail' => UploadedFile::fake()->image('logo.png', 50, 50),
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'warning');
        $response->assertSessionHas('message', function ($message) {
            return str_contains($message, 'Upload failed for:') && str_contains($message, 'Logo Con Scrittura Fallita');
        });
        $this->assertDatabaseHas('cms_settings', [
            'name' => 'logo_fail',
            'content' => '/storage/uploads/2020-01/mantieni.png',
        ]);
    }

    public function test_postsavesetting_salvataggio_riuscito_invalida_la_cache_per_ogni_setting_toccato(): void
    {
        $this->actingAsSuperadmin();
        $this->seedSetting(['name' => 'cache_a', 'group_setting' => 'Gruppo Cache Save', 'content' => 'a']);
        $this->seedSetting(['name' => 'cache_b', 'group_setting' => 'Gruppo Cache Save', 'content' => 'b']);
        Cache::put('setting_cache_a', 'vecchio a');
        Cache::put('setting_cache_b', 'vecchio b');

        $response = $this->post('http://localhost/admin/settings/save-setting', [
            'group_setting' => 'Gruppo Cache Save',
            'cache_a' => 'nuovo a',
            'cache_b' => 'nuovo b',
        ]);

        $response->assertStatus(302);
        $this->assertFalse(Cache::has('setting_cache_a'));
        $this->assertFalse(Cache::has('setting_cache_b'));
    }

    // ---------------------------------------------------------------
    // getDeleteFileSetting()
    // ---------------------------------------------------------------

    public function test_getdeletefilesetting_nega_accesso_a_non_superadmin(): void
    {
        $tenantId = $this->seedTenant();
        $this->actingAsTenantUser($tenantId, isTenantadmin: true, visibleModulePaths: ['settings']);
        $setting = $this->seedSetting([
            'name' => 'file_protetto',
            'content_input_type' => 'upload_image',
            'content' => '/storage/uploads/phpunit-settings-test/protetto.png',
        ]);

        $response = $this->get("http://localhost/admin/settings/delete-file-setting?id={$setting['id']}");

        $response->assertStatus(302);
        $response->assertSessionHas('message', trans('crudbooster.denied_access'));
        $this->assertDatabaseHas('cms_settings', [
            'id' => $setting['id'],
            'content' => '/storage/uploads/phpunit-settings-test/protetto.png',
        ]);
    }

    public function test_getdeletefilesetting_rimuove_il_file_e_azzera_il_content(): void
    {
        $this->actingAsSuperadmin();
        $relativePath = '/storage/uploads/phpunit-settings-test/logo-content.png';
        $this->createFakeFileOnDisk($relativePath);
        $setting = $this->seedSetting([
            'name' => 'logo_da_svuotare',
            'content_input_type' => 'upload_image',
            'content' => $relativePath,
        ]);

        $response = $this->get("http://localhost/admin/settings/delete-file-setting?id={$setting['id']}");

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');
        $this->assertFileDoesNotExist(public_path($relativePath));
        $this->assertDatabaseHas('cms_settings', ['id' => $setting['id'], 'content' => null]);
    }

    public function test_getdeletefilesetting_su_content_gia_vuoto_non_fa_nulla(): void
    {
        $this->actingAsSuperadmin();
        $setting = $this->seedSetting(['name' => 'gia_vuoto', 'content_input_type' => 'upload_image', 'content' => null]);

        $response = $this->get("http://localhost/admin/settings/delete-file-setting?id={$setting['id']}");

        $response->assertStatus(302);
        $response->assertSessionHas('message_type', 'success');
        $this->assertDatabaseHas('cms_settings', ['id' => $setting['id'], 'content' => null]);
    }

    public function test_getdeletefilesetting_invalida_la_cache(): void
    {
        $this->actingAsSuperadmin();
        $relativePath = '/storage/uploads/phpunit-settings-test/logo-cache.png';
        $this->createFakeFileOnDisk($relativePath);
        $setting = $this->seedSetting([
            'name' => 'logo_cache_test',
            'content_input_type' => 'upload_image',
            'content' => $relativePath,
        ]);
        Cache::put('setting_logo_cache_test', 'vecchio-path-in-cache');

        $response = $this->get("http://localhost/admin/settings/delete-file-setting?id={$setting['id']}");

        $response->assertStatus(302);
        $this->assertFalse(Cache::has('setting_logo_cache_test'));
    }

    // ---------------------------------------------------------------
    // CRUDBooster::getSetting()
    // ---------------------------------------------------------------

    public function test_getsetting_legge_dal_db_e_mette_in_cache_forever(): void
    {
        $this->seedSetting(['name' => 'letto_da_db', 'content' => 'valore db']);

        $this->assertSame('valore db', \CRUDBooster::getSetting('letto_da_db'));
        $this->assertTrue(Cache::has('setting_letto_da_db'));
    }

    public function test_getsetting_una_volta_in_cache_ignora_i_cambi_nel_db(): void
    {
        $this->seedSetting(['name' => 'cache_first', 'content' => 'valore originale']);
        \CRUDBooster::getSetting('cache_first');

        DB::table('cms_settings')->where('name', 'cache_first')->update(['content' => 'valore cambiato']);

        $this->assertSame('valore originale', \CRUDBooster::getSetting('cache_first'));
    }

    public function test_getsetting_su_nome_inesistente_torna_null(): void
    {
        $this->assertNull(\CRUDBooster::getSetting('nome_che_non_esiste_mai'));
    }
}
