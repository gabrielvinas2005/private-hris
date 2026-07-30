<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        'department_id',
        'division_chief_id',
        'active'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
