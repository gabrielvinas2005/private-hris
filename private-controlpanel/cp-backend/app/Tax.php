<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'percentage', 'min_amount', 'max_amount', 'base_tax'
    ];
}
