<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OvertimeType extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'rate', 'active', 'nd_from', 'nd_to', 'nd_rating'
    ];
}
