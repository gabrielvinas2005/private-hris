<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeOrganization extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'organization',
        'org_from',
        'org_to',
        'org_hours',
        'org_position'
    ];
}
