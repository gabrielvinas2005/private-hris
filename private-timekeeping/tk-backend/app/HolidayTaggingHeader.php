<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HolidayTaggingHeader extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'holiday_tagging_headers';

    public $timestamps = true;

    protected $fillable = [
        'branch_id', 'holiday_type_id', 'year'
    ];
}
