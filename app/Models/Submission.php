<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'utm_source',
        'utm_medium',
        'utm_campaign',
    ];
}
