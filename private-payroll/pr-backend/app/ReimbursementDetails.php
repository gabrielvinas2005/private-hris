<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class reimbursementdetails extends Model
{

    protected $table = 'reimbursement_details'; // Table name

    protected $fillable = [
        'id',
        'reimbursement_headers_id',
        'employee_id',
        'department_id',
        'prepaid_invoice_no',
        'prepaid_amount',
        'postpaid_invoice_no',
        'postpaid_amount',
    ];


}