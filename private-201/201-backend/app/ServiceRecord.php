<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceRecord extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'designation',
        'employment_type',
        'annual_salary',
        'place_of_assignment',
        'leave_without_pay',
        'separation_date',
        'cause',
        'branch'
    ];
}
