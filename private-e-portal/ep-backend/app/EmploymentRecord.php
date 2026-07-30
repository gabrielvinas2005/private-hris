<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmploymentRecord extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'work_start_date',
        'work_end_date',
        'work_company',
        'monthly_salary',
        'salary_grade_step',
        'status_of_appointment',
        'position',
        'government_service_id'
    ];
}
