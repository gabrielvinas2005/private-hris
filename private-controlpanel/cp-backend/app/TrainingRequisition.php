<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingRequisition extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'srno',
        'request_date',
        'date_from',
        'date_to',
        'start_time',
        'end_time',
        'hrs_day',
        'total_hrs',
        'is_internal',
        'is_paid',
        'training_location',
        'training_type_id',
        'training_title',
        'remarks',
        'status'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
