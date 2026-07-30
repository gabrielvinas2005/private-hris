<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    protected $primaryKey = 'employee_document_id';

    protected $fillable = [
        'employee_id',
        'name',
        'description',
        'attachment_name',
        'path',
        'extension',
        'document_type_id',
    ];
}
