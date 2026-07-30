<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'active'
    ];
}
