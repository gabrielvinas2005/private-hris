<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveCredit extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'credits'
    ];
}
