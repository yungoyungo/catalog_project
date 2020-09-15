<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CarGrade extends Model
{
    protected $fillable = [
        'car_model_id',
        'code',
        'name',
        'capacity',
        'length',
        'width',
        'height',
        'price',
        'start_at',
        'end_at',
        'body_type',
        'description',
        'photo_front_url',
        'photo_front_caption',
        'photo_rear_url',
        'photo_rear_caption',
        'photo_dashboard_url',
        'photo_dashboard_caption',
        'url',
    ];
}
