<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BloodType extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name'
    ];
}
