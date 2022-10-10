<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $table = "quin_files_meta";
    protected $fillable = [
        'name',
        'mime_type',
        'path',
        'created_by',
        'status'
    ];
    protected $hidden = [
        'id'
    ];
}
