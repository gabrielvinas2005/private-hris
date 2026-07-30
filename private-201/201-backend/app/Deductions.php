<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Deductions extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name',
        'active',
        'is_sss',
        'is_gsis',
        'is_philhealth',
        'is_pagibig',
        'uacs',
        'mfo_pap',
        'is_bank'
    ];
}
