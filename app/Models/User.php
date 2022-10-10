<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\QuinRolesHistoryIsPartner as QuinRolesHistoryIsPartnerResource;
use App\Http\Resources\QuinUserIsValidPartner as QuinUserIsValidPartnerResource;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = "users";
    protected $keyType = "integer"; // data type of primary key, optional if primary key is id
    protected $primaryKey = "ID"; // primary key, optional if primary key is id
    public $incrementing = true; // default
    public $timestamps = false; // disabled created_at and updated_at
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_login',
        'user_pass',
        'user_nicename',
        'user_email',
        'user_url',
        'user_registered',
        'user_activation_key',
        'user_status',
        'display_name'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'ID',
        'user_pass'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'user_email';
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->user_pass;
    }

    // relation to self-defined users table
    public function connectedQuinUser(){
        return $this->hasOne(QuinUser::class, 'users_id', 'ID');
    }

    // relation to self-defined roles history table
    public function connectedQuinRolesHistory(){
        return $this->hasMany(QuinRolesHistory::class, 'users_id', 'ID');
    }

    // relation to file table
    public function connectedAvatar(){
        return $this->hasOne(Images::class, 'ID', 'avatar');
    }

    // function check if is partner
    public function isPartner(){
        if(Auth::user()) {
            try {
                $key = QuinRolesHistory::with(['connectedQuinUser', 'connectedQuinRoles'])
                    ->where([
                        ['roles', '>=', 1],
                        ['roles', '<=', 4],
                        ['users_id', '=', Auth::user()->ID],
                        ['created_at', '<', formatDateTimeZone(date('Y-m-d H:i:s'), 1)]
                    ])
                    ->orderByDesc('created_at')
                    ->firstOrFail();

                return new QuinRolesHistoryIsPartnerResource($key);

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
                return null;
            }
        }
        else {
            return null;
        }
    }

    public function isValidPartner() {
        if(Auth::user()) {
            try {
                $validStatus = [21, 23];
                $quinUser = QuinUser::whereIn('status', $validStatus)
                    ->whereNotNull('partner_joined_at')
                    ->where('users_id', '=', Auth::user()->ID)
                    ->firstOrFail();

                return new QuinUserIsValidPartnerResource($quinUser);

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
                return null;
            }
        }
        else {
            return null;
        }
    }
}
