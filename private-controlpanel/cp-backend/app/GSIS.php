<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GSIS extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'year', 'multiflier'
    ];
}
