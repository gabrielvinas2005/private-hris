<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'holiday_type', 'date', 'active'
    ];
}
