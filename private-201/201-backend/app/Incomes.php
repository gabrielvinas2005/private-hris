<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Incomes extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name',
        'is_taxable',
        'is_time_related',
        'active'
    ];

    const CREATED_AT = 'creation_at';
    const UPDATED_AT = 'updated_at';
}
