<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model {
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'total_price',
        'sort_order',
    ];

    protected $casts = [
        'quantity'    => 'float',
        'unit_price'  => 'float',
        'tax_rate'    => 'float',
        'tax_amount'  => 'float',
        'total_price' => 'float',
        'sort_order'  => 'integer',
    ];

    public function invoice(): BelongsTo {
        return $this->belongsTo(Invoice::class);
    }
}
