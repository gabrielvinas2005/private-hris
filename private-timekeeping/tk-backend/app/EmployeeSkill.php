<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeSkill extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'skill'
    ];
}
