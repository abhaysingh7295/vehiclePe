<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffDetails extends Authenticatable implements JWTSubject
{
    use HasFactory;
    
    // protected $table = 'pa_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamps = false;
    public $primaryKey = 'staff_id';

    protected $fillable = [
        'user_id', 'staff_name', 'staff_email', 'staff_mobile_number', 'staff_dob', 'staff_added', 'vendor_id', 'password', 'profile_image', 'address', 'state', 'city', 'login_status', 'active_status', 'last_login', 'access_permission', 'api_permission', 'login_type'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }


    public function getJWTCustomClaims()
    {
        return [
            // Custom claims here
            'user_id' => $this->staff_id,
            'email' => $this->staff_email,
        ];
    }

}
