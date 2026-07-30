<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Learning extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        'active',
    ];
}
