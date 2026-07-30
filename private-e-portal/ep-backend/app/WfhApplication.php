<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WfhApplication extends Model
{
    public $timestamps = false;

    protected $table = 'wfh_application';

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'reason',
        'approved',
        'disapproved',
        'disapproved_reason',
        'processed_at',
        'approved_2',
        'disapproved_2',
        'disapproved_reason_2',
        'processed_at_2',
        'approved_3',
        'disapproved_3',
        'disapproved_reason_3',
        'processed_at_3',
        'cancelled',
        'cancelled_reason',
        'cancelled_at',
        'attachment'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved' => 'boolean',
        'disapproved' => 'boolean',
        'approved_2' => 'boolean',
        'disapproved_2' => 'boolean',
        'approved_3' => 'boolean',
        'disapproved_3' => 'boolean',
        'cancelled' => 'boolean',
        'processed_at' => 'datetime',
        'processed_at_2' => 'datetime',
        'processed_at_3' => 'datetime',
        'cancelled_at' => 'datetime',
    ];
}

