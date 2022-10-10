<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutItem extends Model
{
    protected $id;
    protected $name;
    protected $sales;
    protected $rate;
    protected $total;
    protected $expand;
}

?>