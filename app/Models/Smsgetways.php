<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Smsgetways extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'apikey', 'campaign', 'type', 'routeid', 'routeid2', 'vendorid', 'senderid', 'template_id', 'template_id2', 'template_id3', 'template_id4', 'template_id1_txt', 'template_id2_txt', 'template_id3_txt', 'vendorurl', 'template_id4_txt', 'created_at', 'updated_at'
    ];

    public function vendor()
    {
        return $this->belongsTo(PaUser::class, 'vendor_id');
    }
}


