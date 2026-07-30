<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TimeData extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'payroll_period_id',
        'date',
        'am_in',
        'am_out',
        'break_in',
        'break_out',
        'pm_in',
        'pm_out',
        'work_hours',
        'late',
        'undertime',
        'absent',
        'leave',
        'is_ob',
        'ob_id',
        'is_holiday',
        'holiday_id',
        'holiday_pay',
        'is_ot',
        'ot_id',
        'ot_pay',
        'nd_pay',
        'remarks',
        'is_shifting',
        'work_schedule_id',
        'ob_hours',
        'ot_hours'
    ];
}
