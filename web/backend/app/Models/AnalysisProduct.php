<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisProduct extends Model
{
    protected $fillable = [
        'analysis_id',
        'product_id',

        'weight_g',

        'detected_count',
        'max_confidence',

        'kcal_per_100g',
        'protein_per_100g',
        'fat_per_100g',
        'carbs_per_100g',

        'total_kcal',
        'total_protein',
        'total_fat',
        'total_carbs',
    ];

    protected $casts = [
        'weight_g' => 'decimal:1',

        'max_confidence' => 'decimal:4',

        'kcal_per_100g' => 'decimal:2',
        'protein_per_100g' => 'decimal:2',
        'fat_per_100g' => 'decimal:2',
        'carbs_per_100g' => 'decimal:2',

        'total_kcal' => 'decimal:2',
        'total_protein' => 'decimal:2',
        'total_fat' => 'decimal:2',
        'total_carbs' => 'decimal:2',
    ];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}