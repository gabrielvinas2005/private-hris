<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalaryScheduleDetails extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'salary_schedule_id', 'salary_grade_id', 'salary_step_id', 'amount'
    ];
}
