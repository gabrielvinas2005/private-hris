<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Religion extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'active'
    ];
}
