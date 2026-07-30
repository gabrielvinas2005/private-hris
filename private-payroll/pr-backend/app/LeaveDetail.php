<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveDetail extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'leave_id',
        'leave_date',
        'with_pay',
        'without_pay'
    ];
}
