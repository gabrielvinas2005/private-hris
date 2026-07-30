<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'logo', 'name', 'address', 'email', 'telephone_no', 'mobile_no'
    ];
}
