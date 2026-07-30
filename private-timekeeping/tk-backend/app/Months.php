<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Months extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id',
        'abbrv',
        'name',
    ];

    protected $primaryKey = 'id';
}

