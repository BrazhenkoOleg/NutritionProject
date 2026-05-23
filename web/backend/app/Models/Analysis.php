<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Analysis extends Model
{
    protected $fillable = [
        'user_id',
        'meal_type',
        'entry_date',
        'image_url',
        'image_public_id',
        'status',
        'detections_count',
        'products_count',
        'detections',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'detections' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function analysisProducts(): HasMany
    {
        return $this->hasMany(AnalysisProduct::class);
    }
}