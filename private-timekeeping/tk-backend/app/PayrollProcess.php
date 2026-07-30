<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayrollProcess extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'salary',
        'gsis',
        'sss',
        'pagibig',
        'philhealth',
        'tax',
        'late_amount',
        'ut_amount',
        'absent_amount',
        'holiday_amount',
        'ot_amount',
        'nd_amount',
        'total_income',
        'total_deduction',
        'gross_amount',
        'net_pay'
    ];
}
