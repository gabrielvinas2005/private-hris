<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalnBusinessRelatives extends Model
{
    protected $fillable = [
        'user_id',
        'relatives_name',
        'relationship',
        'position',
        'office_address',
    ];
}
