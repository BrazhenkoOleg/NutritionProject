<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Analysis extends Model
{
    protected $fillable = [
        'user_id',
        'meal_type',
        'entry_date',
        'image_path',
        'image_url',
        'status',
        'detections_count',
        'products_count',
        'detections',
        'products',
        'note',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'detections' => 'array',
        'products' => 'array',
    ];
}