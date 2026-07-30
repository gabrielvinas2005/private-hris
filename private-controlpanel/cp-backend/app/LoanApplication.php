<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LoanApplication extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'deduction_id',
        'employee_id',
        'loan_amount',
        'interest_rate',
        'term',
        'loan_amortization',
        'remarks',
        'effectivity_date',
        'end_date',
        'is_approve',
        'is_disapprove'
    ];
}
