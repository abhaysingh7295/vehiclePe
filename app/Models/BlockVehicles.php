<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockVehicles extends Model
{
    use HasFactory;
    
    // protected $table = 'pa_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vendor_id', 'vehicle_number', 'blocked_time', 'status'
    ];
}
