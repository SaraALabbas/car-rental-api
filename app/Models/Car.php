<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    //
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
    'available'
];
}
