<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'is_administrative_position', 'active'
    ];
}
