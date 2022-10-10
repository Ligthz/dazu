<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuinRoles extends Model
{
    use HasFactory;

    protected $table = "quin_roles_meta";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'short_name',
        'referral_amount',
        'personal_sales_requirement',
        'group_sales_requirement',
        'group_sales_requirement_2',
        'expired_period',
        'maintain_amount',
        'program_entitled',
        'newlaunch_personal_sales_requirement',
        'newlaunch_group_sales_requirement',
        'direct_ba_bonus',
        'direct_bm_bonus',
        'direct_be_bonus',
        'direct_bd_bonus',
        'primary_color',
        'secondary_color',
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
}
