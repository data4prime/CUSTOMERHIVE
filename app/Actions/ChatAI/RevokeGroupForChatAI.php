<?php

namespace App\Actions\ChatAI;

use App\Models\ChatAIGroupAllowed;

class RevokeGroupForChatAI
{
    /**
     * Toglie l'abilitazione del gruppo dalla conf (solo tabella ChatAI). Gli id
     * restano senza tipo: il controller li ha gia' validati con MyHelper::is_int.
     */
    public function handle($confId, $groupId): void
    {
        ChatAIGroupAllowed::where('chatai_conf_id', $confId)
            ->where('group_id', $groupId)
            ->delete();
    }
}
