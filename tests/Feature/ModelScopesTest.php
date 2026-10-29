<?php

namespace Tests\Feature;

use App\Menu;
use App\UsersGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsCmsData;
use Tests\TestCase;

/**
 * Batch 3 del piano 317 (local scope): ogni scope deve produrre lo stesso SQL
 * e gli stessi risultati della query esplicita che sostituisce nei controller
 * e negli helper. Il global scope SoftDeletes di UsersGroup resta attivo.
 */
class ModelScopesTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCmsData;

    private function membership(int $userId, int $groupId, bool $deleted = false): int
    {
        return DB::table('users_groups')->insertGetId([
            'user_id' => $userId,
            'group_id' => $groupId,
            'created_at' => now(),
            'deleted_at' => $deleted ? now() : null,
        ]);
    }

    public function test_menu_active_dashboard_stesso_sql_della_query_esplicita(): void
    {
        $scoped = Menu::activeDashboard();
        $explicit = Menu::where('name', 'Dashboard')->where('is_active', 1)->where('is_dashboard', 1);

        $this->assertSame($explicit->toSql(), $scoped->toSql());
        $this->assertSame($explicit->getBindings(), $scoped->getBindings());
    }

    public function test_menu_active_dashboard_seleziona_solo_la_dashboard_attiva(): void
    {
        $ok = $this->seedMenu(['name' => 'Dashboard', 'is_active' => 1, 'is_dashboard' => 1]);
        $this->seedMenu(['name' => 'Dashboard', 'is_active' => 0, 'is_dashboard' => 1]);
        $this->seedMenu(['name' => 'Dashboard', 'is_active' => 1, 'is_dashboard' => 0]);
        $this->seedMenu(['name' => 'Altro', 'is_active' => 1, 'is_dashboard' => 1]);

        $this->assertSame([$ok['id']], Menu::activeDashboard()->pluck('id')->all());
        $this->assertSame($ok['id'], Menu::activeDashboard()->first()->id);
    }

    public function test_menu_qlik_item_content_stesso_sql_della_query_esplicita(): void
    {
        $scoped = Menu::qlikItemContent(7);
        $explicit = Menu::where('path', 'qlik_items/content/' . 7);

        $this->assertSame($explicit->toSql(), $scoped->toSql());
        $this->assertSame($explicit->getBindings(), $scoped->getBindings());
        $this->assertSame(['qlik_items/content/7'], $scoped->getBindings());
    }

    public function test_menu_qlik_item_content_trova_solo_la_voce_dell_item(): void
    {
        $target = $this->seedMenu(['path' => 'qlik_items/content/5']);
        $this->seedMenu(['path' => 'qlik_items/content/50']);
        $this->seedMenu(['path' => 'qlik_items/content/6']);

        $this->assertSame($target['id'], Menu::qlikItemContent(5)->first()->id);
        $this->assertTrue(Menu::qlikItemContent(5)->exists());
        $this->assertFalse(Menu::qlikItemContent(999)->exists());
    }

    public function test_users_group_of_user_e_of_group_stesso_sql_della_query_esplicita(): void
    {
        $this->assertSame(
            UsersGroup::where('user_id', 3)->toSql(),
            UsersGroup::ofUser(3)->toSql()
        );
        $this->assertSame([3], UsersGroup::ofUser(3)->getBindings());

        $this->assertSame(
            UsersGroup::where('group_id', 4)->toSql(),
            UsersGroup::ofGroup(4)->toSql()
        );
        $this->assertSame([4], UsersGroup::ofGroup(4)->getBindings());

        // catena a due condizioni (add_group, GroupHelper::add): ordine invariato
        $this->assertSame(
            UsersGroup::where('group_id', 4)->where('user_id', 3)->toSql(),
            UsersGroup::ofGroup(4)->where('user_id', 3)->toSql()
        );
        $this->assertSame(
            UsersGroup::where('user_id', 3)->where('group_id', 4)->where('deleted_at', null)->toSql(),
            UsersGroup::ofUser(3)->where('group_id', 4)->where('deleted_at', null)->toSql()
        );
    }

    public function test_users_group_scope_mantengono_il_filtro_soft_delete(): void
    {
        $userId = $this->seedUser()['id'];
        $otherUserId = $this->seedUser()['id'];
        $groupA = $this->seedGroup();
        $groupB = $this->seedGroup();

        $this->membership($userId, $groupA);
        $this->membership($userId, $groupB, deleted: true);
        $this->membership($otherUserId, $groupA);
        $this->membership($otherUserId, $groupB);

        $this->assertSame(
            UsersGroup::where('user_id', $userId)->pluck('group_id')->all(),
            UsersGroup::ofUser($userId)->pluck('group_id')->all()
        );
        $this->assertSame([$groupA], UsersGroup::ofUser($userId)->pluck('group_id')->all());

        $this->assertSame(2, UsersGroup::ofGroup($groupA)->count());
        $this->assertSame(1, UsersGroup::ofGroup($groupB)->count());
        $this->assertSame(
            UsersGroup::where('group_id', $groupB)->count(),
            UsersGroup::ofGroup($groupB)->count()
        );

        // il soft-deleted resta visibile solo con withTrashed() esplicito
        $this->assertSame(2, UsersGroup::withTrashed()->ofUser($userId)->count());
    }

    public function test_users_group_of_user_delete_e_soft_delete_come_prima(): void
    {
        $userId = $this->seedUser()['id'];
        $groupA = $this->seedGroup();
        $groupB = $this->seedGroup();
        $this->membership($userId, $groupA);
        $this->membership($userId, $groupB);

        UsersGroup::ofUser($userId)->where('deleted_at', null)->delete();

        $this->assertSame(0, UsersGroup::ofUser($userId)->count());
        $this->assertSame(2, DB::table('users_groups')->where('user_id', $userId)->whereNotNull('deleted_at')->count());
    }
}
