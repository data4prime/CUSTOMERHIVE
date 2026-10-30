<?php

namespace App\Actions\ChatAI;

use App\Models\ChatAIConf;

class SetPrimaryChatAIConf
{
    /**
     * Regola "una sola conf primary": se la conf e' primary azzera tutte le altre.
     * Query Eloquent (non DB::table) di proposito: ChatAIConf ha SoftDeletes, e le
     * conf cancellate restano com'erano, come prima dell'estrazione. Confronto
     * lasco (== 1) come negli hook originali.
     */
    public function handle(ChatAIConf $conf): void
    {
        if ($conf->primary == 1) {
            ChatAIConf::where('id', '!=', $conf->id)
                ->update(['primary' => 0]);
        }
    }
}
