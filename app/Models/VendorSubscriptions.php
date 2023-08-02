<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorSubscriptions extends Model
{
    use HasFactory;
    
    // protected $table = 'pa_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'subscription_plan_id', 'vendor_id', 'staff_capacity', 'report_export_capacity', 'duration', 'self_parking', 'monthly_pass', 'pre_booking', 'sms_notification', 'auto_email', 'daily_report', 'monthly_report', 'wanted_notification', 'block_vehicle', 'wallet', 'vendor_logo', 'fare_info', 'subscription_amount', 'subscription_start_date', 'subscription_end_date', 'date_time', 'payment_type', 'merchantTxnId', 'txnId', 'status', 'is_paid'
    ];
}
