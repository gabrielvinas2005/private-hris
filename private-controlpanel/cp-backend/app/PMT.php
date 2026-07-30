<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PMT extends Model
{
    protected $table = 'pmt';

    protected $fillable = [
        'employee_id',
        'department_id',
        'division_id',
        'section_id',
        'is_ipcr',
        'is_opcr',
        'is_dpcr'
    ];

    protected $casts = [
        'is_ipcr' => 'boolean',
        'is_opcr' => 'boolean',
        'is_dpcr' => 'boolean',
    ];
}

