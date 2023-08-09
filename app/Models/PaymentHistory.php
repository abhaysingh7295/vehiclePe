<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    use HasFactory;
    
    protected $table = 'payment_history';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'booking_id', 'pre_booking_id', 'order_id', 'amount', 'payment_type', 'transaction_id', 'payment_date_time', 'staff_id'
    ];
}