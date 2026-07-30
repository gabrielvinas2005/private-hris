<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HolidayType extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'rate', 'active'
    ];
}
