<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OvertimeApplication extends Model
{
    public $timestamps = true;

    protected $table = 'overtime_applications';

    protected $fillable = [
        'employee_id', 'overtime_type_id', 'date', 'date_time_from', 'date_time_to', 'total_hours', 'remarks', 'payroll', 'service_credits'
    ];
}
