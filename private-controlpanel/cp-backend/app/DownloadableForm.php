<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DownloadableForm extends Model
{
    protected $table = 'downloadable_forms';
    protected $primaryKey = 'FormId';
    public $timestamps = true;

    protected $fillable = [
        'FormName',
        'FormFile',
    ];
}


