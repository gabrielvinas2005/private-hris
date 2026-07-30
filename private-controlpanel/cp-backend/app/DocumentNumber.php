<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DocumentNumber extends Model
{
    protected $fillable = [
        'id',
        'name',
        'rd_document_number',
        'rd_revision',
        'co_document_number',
        'revision'
    ];
}
