<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SemesterRating extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name',
        'active'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
