<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeRequest extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'status_id',
        'request_date'
    ];
}
