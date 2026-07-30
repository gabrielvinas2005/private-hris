<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MidYearTable extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'months', 'percentage'
    ];
}
