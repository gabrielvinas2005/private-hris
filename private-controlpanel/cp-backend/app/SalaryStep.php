<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalaryStep extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'active'
    ];
}
