<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'class_name',
        'name_ru',
        'kcal_per_100g',
        'protein_per_100g',
        'fat_per_100g',
        'carbs_per_100g',
    ];
}
