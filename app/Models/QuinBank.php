<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuinBank extends Model
{
    use HasFactory;

    protected $table = "quin_banks";

    protected $fillable = [
        'bank_id',
        'bank_name',
        'list_order'
    ];
}
