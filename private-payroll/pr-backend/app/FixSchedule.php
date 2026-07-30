<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FixSchedule extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name',
        'with_am_in',
        'with_am_out',
        'with_break_in',
        'with_break_out',
        'with_pm_in',
        'with_pm_out',
        'no_late',
        'no_undertime'
    ];
}
