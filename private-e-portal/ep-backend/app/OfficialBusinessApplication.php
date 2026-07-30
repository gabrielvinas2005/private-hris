<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OfficialBusinessApplication extends Model
{
    public $timestamps = true;

    protected $table = 'official_business_applications';

    protected $fillable = [
        'employee_id',
        'date',
        'date_time_from',
        'date_time_to',
        'client',
        'telephone_numbers',
        'ob_type',
        'type_id',
        'funds',
        'recommending_approval',
        'recommending_position',
        'approver',
        'approver_position',
        'client',
        'purpose'
    ];
}
