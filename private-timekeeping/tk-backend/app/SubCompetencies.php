<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubCompetencies extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        'description',
        'active'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
