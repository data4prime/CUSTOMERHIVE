<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersGroup extends Model
{
    use SoftDeletes;

    protected $table = 'users_groups';

    public $timestamps = false;

    /**
    * Appartenenze di un utente (il global scope SoftDeletes resta attivo).
    */
    public function scopeOfUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
    * Appartenenze a un gruppo (il global scope SoftDeletes resta attivo).
    */
    public function scopeOfGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }
}
