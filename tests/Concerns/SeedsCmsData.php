<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Helper di seeding condivisi tra i test che hanno bisogno di dati minimi
 * validi in tenants/groups/cms_privileges/cms_users (i campi NOT NULL senza
 * default in questo schema legacy vanno sempre passati esplicitamente:
 * tenants.domain_name, cms_users.tenant/primary_group, ecc.).
 */
trait SeedsCmsData
{
    protected function seedTenant(?string $domainName = null): int
    {
        return DB::table('tenants')->insertGetId([
            'name' => 'Tenant ' . ($domainName ?? uniqid()),
            'domain_name' => $domainName ?? ('tenant-' . uniqid()),
            'created_at' => now(),
        ]);
    }

    protected function seedPrivilege(bool $isSuperadmin = false): int
    {
        return DB::table('cms_privileges')->insertGetId([
            'name' => $isSuperadmin ? 'Superadmin' : 'Standard',
            'is_superadmin' => $isSuperadmin,
            'theme_color' => 'blue',
        ]);
    }

    protected function seedGroup(): int
    {
        return DB::table('groups')->insertGetId([
            'name' => 'Gruppo test',
            'created_at' => now(),
        ]);
    }

    /**
     * Crea un utente cms_users pronto per il login, con override opzionali.
     */
    protected function seedUser(array $overrides = []): array
    {
        $tenantId = $overrides['tenant'] ?? $this->seedTenant();
        $privilegeId = $overrides['id_cms_privileges'] ?? $this->seedPrivilege();
        $groupId = $this->seedGroup();

        $data = array_merge([
            'name' => 'Utente Test',
            'email' => 'utente.test+' . uniqid() . '@example.com',
            'password' => Hash::make('password-corretta-123'),
            'id_cms_privileges' => $privilegeId,
            'status' => 'Active',
            'primary_group' => $groupId,
            'tenant' => $tenantId,
            'created_at' => now(),
        ], $overrides);

        $userId = DB::table('cms_users')->insertGetId($data);

        return array_merge($data, ['id' => $userId]);
    }
}
