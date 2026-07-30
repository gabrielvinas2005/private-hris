<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Plantilla extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'code', 'position_id', 'salary_step_id', 'salary_grade_id', 'active', 'department_id', 'eligibility', 'experience', 'training', 'education', 'unit', 'publication_from', 'publication_to', 'status'
    ];
}
