<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        'functionality',
        'is_academic',
        'active',
        'branch_id',
        'employee_id'
    ];
}
