<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CashGiftTable extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'months', 'percentage'
    ];
}
