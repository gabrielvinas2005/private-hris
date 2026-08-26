<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalnLiability extends Model
{
    protected $fillable = [
        'user_id',
        'nature',
        'creditor_name',
        'outstanding_balance',
    ];
}
