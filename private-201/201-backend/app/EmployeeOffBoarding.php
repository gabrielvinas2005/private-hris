<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeOffBoarding extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'nature_id',
        'remarks',
        'date_effectivity'
    ];
}
