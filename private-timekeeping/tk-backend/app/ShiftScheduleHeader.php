<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShiftScheduleHeader extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name',
        'date_from',
        'date_to'
    ];
}
