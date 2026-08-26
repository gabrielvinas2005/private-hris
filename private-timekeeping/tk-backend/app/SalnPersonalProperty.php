<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalnPersonalProperty extends Model
{
    protected $table = 'saln_personal_properties';

    protected $fillable = [
        'user_id',
        'description',
        'year_acquired',
        'acquisition_cost',
    ];
}
