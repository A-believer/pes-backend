<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model {
    protected $fillable = [
        'type',
        'name',
        'email',
        'phone',
        'company',
        'service',
        'postcode',
        'rating',
        'message',
    ];
}
