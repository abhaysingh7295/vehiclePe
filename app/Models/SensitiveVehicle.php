<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Tymon\JWTAuth\Contracts\JWTSubject;

class SensitiveVehicle extends Model
{
    use HasFactory;
    
    protected $table = 'sensitive_vehicle';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'admin_id', 'open_admin_id', 'close_admin_id', 'crn_no', 'founded_vendor_id', 'vehicle_number', 'vehicle_type', 'mobile_number', 'engine_number', 'chassis_number', 'city', 'state', 'address', 'pin_code', 'polic_station', 'search_reason', 'remark', 'photo_upload', 'status', 'find', 'submit_date_time', 'created_at', 'update_at'
    ];
}
