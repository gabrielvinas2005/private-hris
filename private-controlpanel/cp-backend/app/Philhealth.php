<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Philhealth extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'year', 'multiplier', 'income_floor', 'income_ceiling', 'fix_rate'
    ];
}
