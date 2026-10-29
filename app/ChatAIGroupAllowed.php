<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Riga di autorizzazione gruppo -> conf ChatAI (tabella dedicata, separata da
 * items_allowed dei Qlik items; piano 318).
 */
class ChatAIGroupAllowed extends Model
{
    protected $table = 'chatai_groups_allowed';

    public $timestamps = false;
}
