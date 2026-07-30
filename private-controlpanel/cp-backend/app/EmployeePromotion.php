<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeePromotion extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'position_id',
        'plantilla_id',
        'is_plantilla',
        'is_teaching',
        'nature_of_appointment_id',
        'employment_type_id',
        'department_id',
        'branch_id',
        'payroll_interval_id',
        'old_salary',
        'old_tax_amount',
        'old_gsis_amount',
        'old_sss_amount',
        'old_pagibig_amount',
        'old_philhealth_amount',
        'new_salary',
        'new_tax_amount',
        'new_gsis_amount',
        'new_sss_amount',
        'new_pagibig_amount',
        'new_philhealth_amount',
        'date_position_appointed',
        'date_of_effectivity'
    ];
}
