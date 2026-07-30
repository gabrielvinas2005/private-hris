<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Approvers extends Model
{
    protected $table = 'approver_headers';

    protected $fillable = [
        'id',
        'department_id',
        'approver_id_1',
        'approver_id_2',
        'approver_id_3'
    ];
}
