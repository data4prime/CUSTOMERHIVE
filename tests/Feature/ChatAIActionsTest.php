<?php

namespace Tests\Feature;

use App\Actions\ChatAI\AllowGroupForChatAI;
use App\Actions\ChatAI\AllowTenantForChatAI;
use App\Actions\ChatAI\RevokeGroupForChatAI;
use App\Actions\ChatAI\RevokeTenantForChatAI;
use App\Actions\ChatAI\SetPrimaryChatAIConf;
use App\Models\ChatAIConf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Piano 330, parte 1: le cinque Action di app/Actions/ChatAI/ (regola "una sola
 * conf primary" e scritture sulle tabelle chatai_*_allowed). Come ActionsTest,
 * MySQL vero. Le Allow* restano "count poi insert": nessun duplicato.
 */
class ChatAIActionsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private function seedConf(array $overrides = []): int
    {
        return DB::table('chatai_confs')->insertGetId(array_merge([
            'title' => 'Agente Test',
            'created_at' => now(),
        ], $overrides));
    }

    private function primaryOf(int $id): int
    {
        return (int) DB::table('chatai_confs')->where('id', $id)->value('primary');
    }

    public function test_set_primary_con_primary_1_azzera_le_altre_conf(): void
    {
        $a = $this->seedConf(['primary' => 1]);
        $b = $this->seedConf(['primary' => 1]);

        (new SetPrimaryChatAIConf())->handle(ChatAIConf::find($b));

        $this->assertSame(0, $this->primaryOf($a));
        $this->assertSame(1, $this->primaryOf($b));
    }

    public function test_set_primary_con_primary_0_non_cambia_nulla(): void
    {
        $a = $this->seedConf(['primary' => 1]);
        $b = $this->seedConf(['primary' => 0]);

        (new SetPrimaryChatAIConf())->handle(ChatAIConf::find($b));

        $this->assertSame(1, $this->primaryOf($a));
        $this->assertSame(0, $this->primaryOf($b));
    }

    public function test_set_primary_non_tocca_le_conf_soft_deleted(): void
    {
        $deleted = $this->seedConf(['primary' => 1, 'deleted_at' => now()]);
        $b = $this->seedConf(['primary' => 1]);

        (new SetPrimaryChatAIConf())->handle(ChatAIConf::find($b));

        $this->assertSame(1, $this->primaryOf($deleted));
    }

    public function test_allow_tenant_crea_una_riga_e_non_duplica(): void
    {
        $confId = $this->seedConf();
        $tenantId = $this->seedTenant();
        $action = new AllowTenantForChatAI();

        $action->handle(ChatAIConf::find($confId), $tenantId);
        $action->handle(ChatAIConf::find($confId), $tenantId);

        $this->assertSame(1, DB::table('chatai_tenants_allowed')
            ->where('chatai_conf_id', $confId)->where('tenant_id', $tenantId)->count());
    }

    public function test_allow_group_crea_una_riga_e_non_duplica(): void
    {
        $confId = $this->seedConf();
        $groupId = $this->seedGroup();
        $action = new AllowGroupForChatAI();

        $action->handle(ChatAIConf::find($confId), $groupId);
        $action->handle(ChatAIConf::find($confId), $groupId);

        $this->assertSame(1, DB::table('chatai_groups_allowed')
            ->where('chatai_conf_id', $confId)->where('group_id', $groupId)->count());
    }

    public function test_revoke_tenant_cancella_solo_la_riga_indicata(): void
    {
        $confId = $this->seedConf();
        $otherConfId = $this->seedConf();
        $tenantId = $this->seedTenant();
        $otherTenantId = $this->seedTenant();
        $groupId = $this->seedGroup();
        DB::table('chatai_tenants_allowed')->insert([
            ['chatai_conf_id' => $confId, 'tenant_id' => $tenantId],
            ['chatai_conf_id' => $confId, 'tenant_id' => $otherTenantId],
            ['chatai_conf_id' => $otherConfId, 'tenant_id' => $tenantId],
        ]);
        DB::table('chatai_groups_allowed')->insert(['chatai_conf_id' => $confId, 'group_id' => $groupId]);

        (new RevokeTenantForChatAI())->handle($confId, $tenantId);

        $this->assertSame(0, DB::table('chatai_tenants_allowed')
            ->where('chatai_conf_id', $confId)->where('tenant_id', $tenantId)->count());
        $this->assertSame(1, DB::table('chatai_tenants_allowed')
            ->where('chatai_conf_id', $confId)->where('tenant_id', $otherTenantId)->count());
        $this->assertSame(1, DB::table('chatai_tenants_allowed')
            ->where('chatai_conf_id', $otherConfId)->where('tenant_id', $tenantId)->count());
        $this->assertSame(1, DB::table('chatai_groups_allowed')->count());
    }

    public function test_revoke_group_cancella_solo_la_riga_indicata(): void
    {
        $confId = $this->seedConf();
        $otherConfId = $this->seedConf();
        $groupId = $this->seedGroup();
        $otherGroupId = $this->seedGroup();
        $tenantId = $this->seedTenant();
        DB::table('chatai_groups_allowed')->insert([
            ['chatai_conf_id' => $confId, 'group_id' => $groupId],
            ['chatai_conf_id' => $confId, 'group_id' => $otherGroupId],
            ['chatai_conf_id' => $otherConfId, 'group_id' => $groupId],
        ]);
        DB::table('chatai_tenants_allowed')->insert(['chatai_conf_id' => $confId, 'tenant_id' => $tenantId]);

        (new RevokeGroupForChatAI())->handle($confId, $groupId);

        $this->assertSame(0, DB::table('chatai_groups_allowed')
            ->where('chatai_conf_id', $confId)->where('group_id', $groupId)->count());
        $this->assertSame(1, DB::table('chatai_groups_allowed')
            ->where('chatai_conf_id', $confId)->where('group_id', $otherGroupId)->count());
        $this->assertSame(1, DB::table('chatai_groups_allowed')
            ->where('chatai_conf_id', $otherConfId)->where('group_id', $groupId)->count());
        $this->assertSame(1, DB::table('chatai_tenants_allowed')->count());
    }
}
