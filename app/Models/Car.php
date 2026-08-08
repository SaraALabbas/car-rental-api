<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    //

    protected $casts = [
    'is_maintenance' => 'boolean',
];
    protected $fillable = [
    'name',
    'plate_number',
    'color',
    'daily_km',
    'price',
    'model_year',
    'image1',
    'image2',
    'image3',
    'available',
    'is_maintenance',
    'seats',
'transmission',
'fuel_type',
'insurance',
];
}
