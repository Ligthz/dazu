<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyPayout extends Model
{
    use HasFactory;

    protected $table = "quin_monthly_payout";
    protected $keyType = "string";
    protected $primaryKey = "payout_id";
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payout_id',
        'date',
        'partner_id',
        'amount',
        'account_details',
        'payout_details',
        'status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];
}
