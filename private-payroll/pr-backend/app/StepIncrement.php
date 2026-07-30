<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StepIncrement extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'effectivity_date',
        'current_salary_grade_id',
        'current_salary_step_id',
        'current_salary',
        'new_salary_grade_id',
        'new_salary_step_id',
        'new_salary',
        'new_tax_amount',
        'new_gsis_amount',
        'new_sss_amount',
        'new_pagibig_amount',
        'new_philhealth_amount'
    ];
}
