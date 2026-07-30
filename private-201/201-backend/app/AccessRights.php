<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccessRights extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'menu_id', 'user_id', 'status'
    ];
}
