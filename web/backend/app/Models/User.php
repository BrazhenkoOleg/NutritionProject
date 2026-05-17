<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'age',
        'height_cm',
        'weight_kg',
        'activity_level',
        'goal',
        'daily_kcal_goal',
        'daily_protein_goal',
        'daily_fat_goal',
        'daily_carbs_goal',
        'profile_completed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'age' => 'integer',
            'height_cm' => 'decimal:1',
            'weight_kg' => 'decimal:1',
            'daily_kcal_goal' => 'integer',
            'daily_protein_goal' => 'decimal:2',
            'daily_fat_goal' => 'decimal:2',
            'daily_carbs_goal' => 'decimal:2',
            'profile_completed' => 'boolean',
        ];
    }
}