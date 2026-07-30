<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayrollCutOff extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name',
        'payroll_interval_id'
    ];
}
