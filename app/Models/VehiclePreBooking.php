<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehiclePreBooking extends Model
{
    use HasFactory;
    protected $table = 'vehicle_pre_booking';

    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id', 'vendor_id', 'arriving_time', 'leaving_time', 'vehicle_number', 'vehicle_type', 'latitude', 'longitude', 'booking_date_time', 'amount', 'payment_type', 'transaction_id', 'status'
    ];
}