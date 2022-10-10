<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuinRolesHistory extends Model
{
    use HasFactory;

    protected $table = "quin_roles_history_meta";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'users_id',
        'roles',
        'created_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id'
    ];

    // relation to self-defined roles table
    public function connectedQuinRoles(){
        return $this->hasOne(QuinRoles::class, 'id', 'roles');
    }

    // relation to self-defined roles table
    public function connectedUser(){
        return $this->hasOne(User::class, 'ID', 'users_id');
    }

    // relation to self-defined roles table
    public function connectedQuinUser(){
        return $this->hasOne(QuinUser::class, 'users_id', 'users_id');
    }
}
