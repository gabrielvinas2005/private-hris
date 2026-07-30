<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestForPickup extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $table = 'request_for_pickup';

    protected $fillable = [
        'ob_id',
        'from',
        'company',
        'address',
        'contact_no',
        'documents_materials',
        'picked_up_by',
        'pickup_date',
        'requested_by',
        'requested_by_employee_id',
        'received_by',
        'date'
    ];

    /**
     * Get the official business application that owns this request for pickup.
     */
    public function officialBusinessApplication()
    {
        return $this->belongsTo(OfficialBusinessApplication::class, 'ob_id');
    }
}
