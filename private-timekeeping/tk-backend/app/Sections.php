<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sections extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        'division_id',
        'section_chief_id',
        'active'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
