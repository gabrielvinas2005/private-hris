<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeReferences extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'ref_name',
        'ref_address',
        'ref_occupation',
        'ref_contact_no'
    ];
}
