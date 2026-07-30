<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IPCR extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'numerical_rating',
        'adjectival_rating',
        'date_from',
        'date_to',
        'year',
        'progress'
    ];
    const CREATED_AT = 'creation_at';
    const UPDATED_AT = 'updated_at';
}
