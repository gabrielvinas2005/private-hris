<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeExamination extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'eligibility_id',
        'exam_rating',
        'exam_date',
        'place_of_exam',
        'license_number',
        'date_released'
    ];
}
