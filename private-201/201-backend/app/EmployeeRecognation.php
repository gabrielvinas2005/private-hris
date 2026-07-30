<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeRecognation extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'recognation'
    ];
}
