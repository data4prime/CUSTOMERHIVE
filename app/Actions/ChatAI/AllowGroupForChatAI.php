<?php

namespace App\Actions\ChatAI;

use App\Models\ChatAIConf;
use App\Models\ChatAIGroupAllowed;

class AllowGroupForChatAI
{
    /**
     * Abilita il gruppo sulla conf. Count poi insert (non firstOrCreate), come
     * prima. $groupId senza tipo per salvare lo stesso valore di prima.
     */
    public function handle(ChatAIConf $conf, $groupId): void
    {
        $access = ChatAIGroupAllowed::where('chatai_conf_id', $conf->id)
            ->where('group_id', $groupId)
            ->count();

        if ($access == 0) {
            $add_group = new ChatAIGroupAllowed();
            $add_group->chatai_conf_id = $conf->id;
            $add_group->group_id = $groupId;
            $add_group->save();
        }
    }
}
