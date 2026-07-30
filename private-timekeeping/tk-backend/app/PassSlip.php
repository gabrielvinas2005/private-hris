<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PassSlip extends Model
{
    public $timestamps = true;

    protected $table = 'pass_slips';

    protected $fillable = [
        'employee_id',
        'date',
        'time_out',
        'time_in',
        'destination',
        'purpose',
        'approved_by',
        'division_chief',
        'status',
        'remarks',
        'approved_at',
        'active',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'active' => 'boolean',
    ];
}
