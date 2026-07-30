<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Eligibility extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'active'
    ];
}
