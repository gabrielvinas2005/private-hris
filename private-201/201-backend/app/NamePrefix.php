<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NamePrefix extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        'active'
    ];
}
