<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveHeader extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'day_type_id',
        'date_from',
        'date_to',
        'reason',
        'approved',
        'disapproved',
        'processed_by',
        'processed_date'
    ];
}
