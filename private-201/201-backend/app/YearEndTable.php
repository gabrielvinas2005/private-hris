<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class YearEndTable extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'months', 'percentage'
    ];
}
