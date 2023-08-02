<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use App\Models\PaUsers;

class VehicleBooking extends Model
{
    use HasFactory;
    protected $table = 'vehicle_booking';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id', 'vendor_id', 'arriving_time', 'leaving_time', 'vehicle_number', 'vehicle_type', 'latitude', 'longitude', 'booking_date_time', 'amount', 'payment_type', 'transaction_id', 'status'
    ];
    public $timestamps = false;
    public function vendor()
    {
        return $this->belongsTo(PaUsers::class, 'vendor_id');
    }

    public function payment_history1()
    {
        return $this->hasMany(PaymentHistory::class, 'id','vendor_id');
        
    }

    public function monthly_pass1()
    {
        // return $this->hasMany(MonthlyPass::class, ['vendor_id', 'vendor_id']);
        return $this->hasManyThrough(PaUsers::class, MonthlyPass::class);
        
    }
}