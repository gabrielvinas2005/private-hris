<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayrollInterval extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name',
        'day_interval',
        'month_frequency',
        'year_frequency'
    ];
}
