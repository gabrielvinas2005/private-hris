<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalaryGrade extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'active'
    ];
}
