<?php

namespace App\Actions\ChatAI;

use App\Models\ChatAITenantAllowed;

class RevokeTenantForChatAI
{
    /**
     * Toglie l'abilitazione del tenant dalla conf (solo tabella ChatAI). Gli id
     * restano senza tipo: il controller li ha gia' validati con MyHelper::is_int.
     */
    public function handle($confId, $tenantId): void
    {
        ChatAITenantAllowed::where('chatai_conf_id', $confId)
            ->where('tenant_id', $tenantId)
            ->delete();
    }
}
