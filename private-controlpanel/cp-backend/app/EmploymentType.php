<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmploymentType extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name',
        'code',
        'with_end_contract',
        'active'
    ];
}
