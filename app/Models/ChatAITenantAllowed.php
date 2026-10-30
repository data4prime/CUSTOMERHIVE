<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Riga di autorizzazione tenant -> conf ChatAI (tabella dedicata, separata da
 * tenants_allowed dei Qlik items; piano 318).
 */
class ChatAITenantAllowed extends Model
{
    protected $table = 'chatai_tenants_allowed';

    public $timestamps = false;
}
