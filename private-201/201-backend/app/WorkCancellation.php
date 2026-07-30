<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkCancellation extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'date_from',
        'date_to',
        'with_pay',
        'reason'
    ];
}
