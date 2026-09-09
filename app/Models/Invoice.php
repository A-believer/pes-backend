<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model {
    use HasFactory;

    protected $fillable = [
        'document_type',
        'document_number',
        'quote_request_id',
        'submission_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_company',
        'billing_address',
        'service_address',
        'service_type',
        'work_status',
        'work_scheduled_date',
        'work_completed_date',
        'issue_date',
        'due_date',
        'payment_date',
        'payment_method',
        'payment_reference',
        'status',
        'currency',
        'subtotal',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'bank_details_json',
        'notes',
        'terms_and_conditions',
        'email_sent_at',
        'email_last_sent_to',
    ];

    protected $casts = [
        'work_scheduled_date' => 'date:Y-m-d',
        'work_completed_date' => 'date:Y-m-d',
        'issue_date'          => 'date:Y-m-d',
        'due_date'            => 'date:Y-m-d',
        'payment_date'        => 'date:Y-m-d',
        'bank_details_json'   => 'array',
        'email_sent_at'       => 'datetime',
        'subtotal'            => 'float',
        'discount_amount'     => 'float',
        'tax_rate'            => 'float',
        'tax_amount'          => 'float',
        'total_amount'        => 'float',
        'amount_paid'         => 'float',
        'balance_due'         => 'float',
    ];

    public function items(): HasMany {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order', 'asc');
    }

    public function quoteRequest(): BelongsTo {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function submission(): BelongsTo {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Generate sequential document number (e.g. INV-2026-0001, REC-2026-0001)
     */
    public static function generateNextDocumentNumber(string $type = 'invoice'): string {
        $prefix = ($type === 'receipt') ? 'REC' : 'INV';
        $year = date('Y');
        
        $latest = self::where('document_type', $type)
            ->where('document_number', 'like', "{$prefix}-{$year}-%")
            ->latest('id')
            ->first();

        if ($latest && preg_match("/{$prefix}-{$year}-(\d+)/", $latest->document_number, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        } else {
            $count = self::where('document_type', $type)->count();
            $nextSeq = $count + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $nextSeq);
    }
}
