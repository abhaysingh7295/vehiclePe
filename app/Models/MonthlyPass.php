<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyPass extends Model
{
    use HasFactory;
    protected $table = 'monthly_pass';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vendor_id', 'customer_id', 'person_name', 'vehicle_number', 'vehicle_type', 'company_name', 'mobile_number', 'amount', 'start_date', 'end_date', 'pass_issued_date', 'payment_type', 'transaction_id', 'user_image', 'vehicle_image', 'status', 'grace_date', 'staffid', 'chechis_number', 'dataupdate_type', 'pass_location', 'pass_type', 'parking_image',
    ];
}