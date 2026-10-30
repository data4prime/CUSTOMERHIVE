<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable;
    use HasApiTokens;

    protected $table = 'cms_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'status', 'id_cms_privileges'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    //nomi diversi da tenant/primary_group: una relazione omonima verrebbe
    //oscurata dall'attributo (colonna) con lo stesso nome
    public function tenantRecord(): BelongsTo
    {
      return $this->belongsTo(Tenant::class, 'tenant');
    }

    public function primaryGroupRecord(): BelongsTo
    {
      return $this->belongsTo(Group::class, 'primary_group');
    }

    /**
     * Groups the user belongs to (pivot users_groups, memberships not soft-deleted).
     */
    public function memberGroups(): BelongsToMany
    {
      return $this->belongsToMany(Group::class, 'users_groups', 'user_id', 'group_id')
                  ->wherePivotNull('deleted_at');
    }

    public function primary_group(){
      return Group::find($this->primary_group);
    }

    public function tenant(){
      return Tenant::find($this->tenant);
    }

    public function isTenantAdmin()
    {
      $my_role_id = $this->id_cms_privileges;
      $role = Role::find($my_role_id);
      //un privilegio cancellato (riferimento orfano) non deve far crashare
      //la pagina: un utente senza privilegio valido non ha permessi speciali
      return $role && $role->is_tenantadmin == 1;
    }

    public function isSuperAdmin()
    {
      $my_role_id = $this->id_cms_privileges;
      $role = Role::find($my_role_id);
      return $role && $role->is_superadmin == 1;
    }

    /**
    * returns user's photo path or a default one based on his privilege
    */
    public function photo()
    {
      if(!empty($this->photo)){
        return $this->photo;
      }
      elseif($this->isSuperAdmin()){
        return asset('/images/user/admin.jpeg');
      }
      elseif($this->isTenantAdmin()){
        return asset('/images/user/manager.jpeg');
      }
      else{
        return asset('/images/user/user.png');
      }
    }
}
