<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'name',
        'is_main_branch',
        'branch_head_id',
    ];
}
