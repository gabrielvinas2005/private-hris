<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OfficialBusinessType extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'active'
    ];
}
