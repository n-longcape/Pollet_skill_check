<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargeHistory extends Model
{
    protected $fillable = [
        'user_id', 'amount', 'status'
    ];
}
