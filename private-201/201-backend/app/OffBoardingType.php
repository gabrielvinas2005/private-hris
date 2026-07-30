<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OffBoardingType extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'active'
    ];
}
