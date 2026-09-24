<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Submission extends Model {
    protected $fillable = [
        'type',
        'landing_page_slug',
        'name',
        'email',
        'phone',
        'company',
        'service',
        'postcode',
        'rating',
        'message',
        'cv_path',
        'cv_original_name',
        'availability',
        'experience_level',
        'has_right_to_work',
        'has_driving_licence',
        'utm_source',
        'utm_medium',
        'utm_campaign',
    ];

    protected $casts = [
        'has_right_to_work' => 'boolean',
        'has_driving_licence' => 'boolean',
    ];

    protected $appends = ['cv_url'];

    public function getCvUrlAttribute(): ?string {
        if (!$this->cv_path) {
            return null;
        }

        // Return direct storage URL if file exists
        return Storage::disk('public')->url($this->cv_path);
    }
}
