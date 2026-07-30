<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShiftScheduleDetails extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'shift_schedule_id',
        'shift_date',
        'am_in',
        'am_out',
        'break_in',
        'break_out',
        'pm_in',
        'pm_out',
        'grace_period',
        'flexi_hours',
        'work_hours'
    ];
}
