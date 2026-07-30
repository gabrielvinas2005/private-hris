<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeMembership extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'membership'
    ];
}
