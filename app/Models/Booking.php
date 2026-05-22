<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    //
    protected $fillable = [
    'car_id', 
    'user_id',
    'full_name',
    'phone',
    'pickup_date',
    'return_date',
    'pickup_time',
'return_time',
    'delivery',
    'delivery_location',
    'id_front',
    'id_back',
    'payment_image',
    'status',
    'rejection_reason',
    

];
public function car()
    {
        return $this->belongsTo(Car::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
