<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Tymon\JWTAuth\Contracts\JWTSubject;

class FareInfo extends Model
{
    use HasFactory;
    
    protected $table = 'fare_info';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'initial_hr', 'initial_min', 'ending_hr', 'ending_min', 'amount', 'hr_status', 'veh_type'
    ];
}
