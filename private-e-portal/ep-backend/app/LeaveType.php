<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name',
        'active'
    ];
}
