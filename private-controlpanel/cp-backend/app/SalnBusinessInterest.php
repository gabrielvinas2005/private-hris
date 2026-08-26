<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalnBusinessInterest extends Model
{
    protected $fillable = [
        'user_id',
        'entity_name',
        'business_address',
        'nature_of_business',
        'date_acquired',
    ];
}
