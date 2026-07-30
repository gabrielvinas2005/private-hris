<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayrollItemScheduleHeader extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */

    protected $table = 'payroll_item_schedule_headers';

    public $timestamps = true;

    protected $fillable = [
        'payroll_interval_type_id',
        'payroll_period_type_id',
        'employment_type_id',
        'sss',
        'gsis',
        'tax',
        'philhealth',
        'pagibig'
    ];
}
