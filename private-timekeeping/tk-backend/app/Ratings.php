<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ratings extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'numerical_rating1',
        'numerical_rating2',
        'adjectival_rating',
        'active'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
