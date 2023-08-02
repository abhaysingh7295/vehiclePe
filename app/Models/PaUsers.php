<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Tymon\JWTAuth\Contracts\JWTSubject;

class PaUsers extends Model
{
    use HasFactory;
    
    // protected $table = 'pa_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','vendor_id','customer_id','serial_number','mobile_number','vehicle_number','vehicle_type','vehicle_in_date_time','vehicle_out_date_time','vehicle_status','latitude','longitude','qr_type','parking_type','staff_vehicle_type','in_amount','in_pay_type','staff_in','out_amount','out_pay_type','staff_out','total_amount','created_at','update_at','vehicle_image','tagid','updated','msgId','trans_response','trans_status_code','sr_no','pass_id'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function vehicleBookings()
    {
        return $this->hasMany(VehicleBooking::class, 'vendor_id');
    }
}
