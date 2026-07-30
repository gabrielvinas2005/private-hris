<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NameSuffix extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'active'
    ];
}
