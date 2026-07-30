<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeTraining extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'training',
        'training_from',
        'training_to',
        'hours',
        'sponsored_by',
        'learning_id'
    ];
}
