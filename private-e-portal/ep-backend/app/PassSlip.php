<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PassSlip extends Model
{
    public $timestamps = true;

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
        'active'
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'active' => 'boolean'
    ];

    /**
     * Get the employee that owns the pass slip.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the approver employee.
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }
}
