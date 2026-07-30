<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeChildren extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'child_name',
        'child_birthdate'
    ];
}
