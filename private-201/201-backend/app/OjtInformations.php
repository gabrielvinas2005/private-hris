<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OjtInformations extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name', 'date_start', 'date_end', 'hours', 'signatory_name', 'signatory_position'
    ];
}
