<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyCommissions extends Model
{
    use HasFactory;

    protected $table = "quin_monthly_commissions";
    protected $keyType = "string";
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'users_key',
        'referral_code',
        'role',
        'personal_sales',
        'personal_bonus',
        'peronsal_commissions',
        'referral_sales',
        'referral_bonus',
        'referral_commissions',
        'dir_ba_count',
        'dir_ba_sales',
        'dir_ba_bonus',
        'dir_ba_commissions',
        'dir_be_count',
        'dir_be_sales',
        'dir_be_bonus',
        'dir_be_commissions',
        'dir_bm_count',
        'dir_bm_sales',
        'dir_bm_bonus',
        'dir_bm_commissions',
        'dir_bd_count',
        'dir_bd_sales',
        'dir_bd_bonus',
        'dir_bd_commissions',
        'grp_ba_count',
        'grp_ba_sales',
        'grp_be_count',
        'grp_be_sales',
        'grp_bm_count',
        'grp_bm_sales',
        'personal_vol_bonus',
        'personal_vol_commissions',
        'first_bd_count',
        'first_bd_sales',
        'first_bd_bonus',
        'first_bd_commissions',
        'second_bd_count',
        'second_bd_sales',
        'second_bd_bonus',
        'second_bd_commissions',
        'payout_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id'
    ];
}
