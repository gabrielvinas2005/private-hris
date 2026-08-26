<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalnRealProperty extends Model
{

    protected $table = 'saln_real_properties';

    protected $fillable = [
        'user_id',
        'description',
        'kind',
        'exact_location',
        'assessed_value',
        'current_fair_market_value',
        'acquisition_year',
        'acquisition_mode',
        'acquisition_cost',
    ];
}
