<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingRequisitioners extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'srno',
        'branch_id',
        'department_id',
        'division_chief_id',
        'section_chief_id',
        'hrdd_id',
        'rd_officer_id',
        'is_division_chief',
        'is_section_chief'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
