<?php

namespace App\Actions\ChatAI;

use App\Models\ChatAIConf;
use App\Models\ChatAITenantAllowed;

class AllowTenantForChatAI
{
    /**
     * Abilita il tenant sulla conf. Count poi insert (non firstOrCreate), come
     * prima: sotto concorrenza il comportamento resta lo stesso. $tenantId senza
     * tipo per salvare lo stesso valore di prima.
     */
    public function handle(ChatAIConf $conf, $tenantId): void
    {
        $allowed = ChatAITenantAllowed::where('chatai_conf_id', $conf->id)
            ->where('tenant_id', $tenantId)
            ->count();

        if ($allowed == 0) {
            $add_tenant = new ChatAITenantAllowed();
            $add_tenant->chatai_conf_id = $conf->id;
            $add_tenant->tenant_id = $tenantId;
            $add_tenant->save();
        }
    }
}
