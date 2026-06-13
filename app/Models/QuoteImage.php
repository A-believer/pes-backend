<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteImage extends Model {
    public $timestamps = false;

    protected $fillable = [
        'quote_request_id',
        'image_url',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function quoteRequest(): BelongsTo {
        return $this->belongsTo(QuoteRequest::class);
    }
}
