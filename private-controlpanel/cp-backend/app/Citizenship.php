<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Citizenship extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'active'
    ];
}
