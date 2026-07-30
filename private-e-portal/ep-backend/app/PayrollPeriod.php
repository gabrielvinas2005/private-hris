<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'payroll_interval_id',
        'payroll_cutoff_id',
        'attendance_start_date',
        'attendance_end_date',
        'payroll_start_date',
        'payroll_end_date',
        'release_date',
        'posted',
        'active'
    ];
}
