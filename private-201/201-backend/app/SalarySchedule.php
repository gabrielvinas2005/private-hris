<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalarySchedule extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'enabling_law', 'effectivity', 'active'
    ];
}
