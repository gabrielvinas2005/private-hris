<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeEducation extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'academic_level_id',
        'school_name',
        'program',
        'from',
        'to',
        'graduated_year',
        'units_earned',
        'honors'
    ];
}
