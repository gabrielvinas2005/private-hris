<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PromotionType extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'active'
    ];
}
