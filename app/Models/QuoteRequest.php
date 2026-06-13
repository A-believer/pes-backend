<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteRequest extends Model {
    use HasFactory;
    protected $fillable = [
        'quote_reference',
        'customer_name',
        'email',
        'phone',
        'address',
        'postcode',
        'city',
        'property_type',
        'floor_area',
        'floors',
        'service_type',
        'frequency',
        'preferred_date',
        'urgency',
        'property_details_json',
        'condition_json',
        'addons_json',
        'photos_json',
        'ai_analysis_json',
        'estimated_hours',
        'difficulty_multiplier',
        'confidence_score',
        'quote_min',
        'quote_max',
        'recommended_cleaners',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'property_details_json' => 'array',
        'condition_json'        => 'array',
        'addons_json'           => 'array',
        'photos_json'           => 'array',
        'ai_analysis_json'      => 'array',
        'floor_area'            => 'decimal:2',
        'difficulty_multiplier' => 'decimal:2',
        'estimated_hours'       => 'decimal:2',
        'quote_min'             => 'decimal:2',
        'quote_max'             => 'decimal:2',
        'preferred_date'        => 'date',
    ];

    public function images(): HasMany {
        return $this->hasMany(QuoteImage::class);
    }

    /**
     * Generate a human-readable quote reference: QT-YYYY-XXXXXX
     */
    public static function generateReference(): string {
        $year = now()->year;
        $lastId = static::whereYear('created_at', $year)->max('id') ?? 0;
        $nextNum = $lastId + 1;
        return sprintf('QT-%d-%06d', $year, $nextNum);
    }

    public function isPending(): bool {
        return $this->status === 'pending';
    }

    public function isAnalysisComplete(): bool {
        return !is_null($this->quote_min) && !is_null($this->quote_max);
    }
}
