<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CustomerDocumentMail;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InvoiceAdminController extends Controller {
    /**
     * GET /api/admin/invoices
     */
    public function index(Request $request): JsonResponse {
        $query = Invoice::with('items')->latest();

        // Search
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('service_type', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($type = $request->query('document_type')) {
            $query->where('document_type', $type);
        }
        if ($workStatus = $request->query('work_status')) {
            $query->where('work_status', $workStatus);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('issue_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('issue_date', '<=', $dateTo);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $paginated = $query->paginate($perPage);

        // Overall KPIs
        $kpis = [
            'total_invoiced'        => (float) Invoice::where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_collected'       => (float) Invoice::where('status', '!=', 'cancelled')->sum('amount_paid'),
            'total_outstanding'     => (float) Invoice::where('status', '!=', 'cancelled')->sum('balance_due'),
            'overdue_count'         => Invoice::where('status', 'overdue')->count(),
            'work_to_be_done_count' => Invoice::where('work_status', 'work_to_be_done')->count(),
            'work_done_count'       => Invoice::where('work_status', 'work_done')->count(),
            'total_count'           => Invoice::count(),
        ];

        return response()->json([
            'data'         => $paginated->items(),
            'total'        => $paginated->total(),
            'per_page'     => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'kpis'         => $kpis,
        ]);
    }

    /**
     * GET /api/admin/invoices/{id}
     */
    public function show(int $id): JsonResponse {
        $invoice = Invoice::with(['items', 'quoteRequest', 'submission'])->find($id);

        if (!$invoice) {
            return response()->json(['error' => 'Document not found.'], 404);
        }

        return response()->json($invoice);
    }

    /**
     * POST /api/admin/invoices
     */
    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'document_type'        => 'required|in:invoice,receipt',
            'document_number'      => 'nullable|string|max:50|unique:invoices,document_number',
            'quote_request_id'     => 'nullable|exists:quote_requests,id',
            'submission_id'        => 'nullable|exists:submissions,id',
            'customer_name'        => 'required|string|max:150',
            'customer_email'       => 'required|email|max:150',
            'customer_phone'       => 'nullable|string|max:50',
            'customer_company'     => 'nullable|string|max:150',
            'billing_address'      => 'required|string',
            'service_address'      => 'nullable|string',
            'service_type'         => 'required|string|max:100',
            'work_status'          => 'required|in:work_to_be_done,work_done',
            'work_scheduled_date'  => 'nullable|date',
            'work_completed_date'  => 'nullable|date',
            'issue_date'           => 'required|date',
            'due_date'             => 'nullable|date',
            'payment_date'         => 'nullable|date',
            'payment_method'       => 'nullable|string|max:50',
            'payment_reference'    => 'nullable|string|max:100',
            'status'               => 'nullable|in:draft,sent,paid,partially_paid,overdue,cancelled',
            'currency'             => 'nullable|string|max:10',
            'discount_amount'      => 'nullable|numeric|min:0',
            'tax_rate'             => 'nullable|numeric|min:0',
            'amount_paid'          => 'nullable|numeric|min:0',
            'bank_details_json'    => 'nullable|array',
            'notes'                => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.description'  => 'required|string|max:255',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.tax_rate'     => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $docType = $validated['document_type'];
            $docNumber = $validated['document_number'] ?? Invoice::generateNextDocumentNumber($docType);

            // Compute line items totals
            $itemsData = $validated['items'];
            $subtotal = 0.0;
            $taxAmount = 0.0;
            $taxRateDefault = $validated['tax_rate'] ?? 20.0;

            $computedItems = [];
            foreach ($itemsData as $idx => $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $itemTaxRate = isset($item['tax_rate']) ? (float) $item['tax_rate'] : $taxRateDefault;
                
                $lineTotal = round($qty * $unitPrice, 2);
                $lineTax = round($lineTotal * ($itemTaxRate / 100), 2);
                
                $subtotal += $lineTotal;
                $taxAmount += $lineTax;

                $computedItems[] = [
                    'description' => $item['description'],
                    'quantity'    => $qty,
                    'unit_price'  => $unitPrice,
                    'tax_rate'    => $itemTaxRate,
                    'tax_amount'  => $lineTax,
                    'total_price' => $lineTotal,
                    'sort_order'  => $idx + 1,
                ];
            }

            $discount = (float) ($validated['discount_amount'] ?? 0.0);
            $totalAmount = max(0.0, round($subtotal - $discount + $taxAmount, 2));
            $amountPaid = (float) ($validated['amount_paid'] ?? 0.0);
            
            // If it's a receipt, assume full payment by default unless specified
            if ($docType === 'receipt' && !isset($validated['amount_paid'])) {
                $amountPaid = $totalAmount;
            }

            $balanceDue = max(0.0, round($totalAmount - $amountPaid, 2));

            // Derive status
            $status = $validated['status'] ?? 'draft';
            if ($balanceDue <= 0 && $totalAmount > 0) {
                $status = 'paid';
            } elseif ($amountPaid > 0 && $balanceDue > 0) {
                $status = 'partially_paid';
            }

            $invoice = Invoice::create([
                'document_type'        => $docType,
                'document_number'      => $docNumber,
                'quote_request_id'     => $validated['quote_request_id'] ?? null,
                'submission_id'        => $validated['submission_id'] ?? null,
                'customer_name'        => $validated['customer_name'],
                'customer_email'       => $validated['customer_email'],
                'customer_phone'       => $validated['customer_phone'] ?? null,
                'customer_company'     => $validated['customer_company'] ?? null,
                'billing_address'      => $validated['billing_address'],
                'service_address'      => $validated['service_address'] ?? null,
                'service_type'         => $validated['service_type'],
                'work_status'          => $validated['work_status'],
                'work_scheduled_date'  => $validated['work_scheduled_date'] ?? null,
                'work_completed_date'  => $validated['work_completed_date'] ?? null,
                'issue_date'           => $validated['issue_date'],
                'due_date'             => $validated['due_date'] ?? null,
                'payment_date'         => $validated['payment_date'] ?? ($status === 'paid' ? now() : null),
                'payment_method'       => $validated['payment_method'] ?? null,
                'payment_reference'    => $validated['payment_reference'] ?? null,
                'status'               => $status,
                'currency'             => $validated['currency'] ?? 'GBP',
                'subtotal'             => $subtotal,
                'discount_amount'      => $discount,
                'tax_rate'             => $taxRateDefault,
                'tax_amount'           => $taxAmount,
                'total_amount'         => $totalAmount,
                'amount_paid'          => $amountPaid,
                'balance_due'          => $balanceDue,
                'bank_details_json'    => $validated['bank_details_json'] ?? null,
                'notes'                => $validated['notes'] ?? null,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
            ]);

            foreach ($computedItems as $item) {
                $invoice->items()->create($item);
            }

            return response()->json([
                'message' => ucfirst($docType) . ' created successfully.',
                'invoice' => $invoice->load('items'),
            ], 201);
        });
    }

    /**
     * PUT /api/admin/invoices/{id}
     */
    public function update(Request $request, int $id): JsonResponse {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json(['error' => 'Document not found.'], 404);
        }

        $validated = $request->validate([
            'document_type'        => 'sometimes|in:invoice,receipt',
            'document_number'      => "sometimes|string|max:50|unique:invoices,document_number,{$id}",
            'customer_name'        => 'sometimes|string|max:150',
            'customer_email'       => 'sometimes|email|max:150',
            'customer_phone'       => 'nullable|string|max:50',
            'customer_company'     => 'nullable|string|max:150',
            'billing_address'      => 'sometimes|string',
            'service_address'      => 'nullable|string',
            'service_type'         => 'sometimes|string|max:100',
            'work_status'          => 'sometimes|in:work_to_be_done,work_done',
            'work_scheduled_date'  => 'nullable|date',
            'work_completed_date'  => 'nullable|date',
            'issue_date'           => 'sometimes|date',
            'due_date'             => 'nullable|date',
            'payment_date'         => 'nullable|date',
            'payment_method'       => 'nullable|string|max:50',
            'payment_reference'    => 'nullable|string|max:100',
            'status'               => 'sometimes|in:draft,sent,paid,partially_paid,overdue,cancelled',
            'currency'             => 'sometimes|string|max:10',
            'discount_amount'      => 'nullable|numeric|min:0',
            'tax_rate'             => 'nullable|numeric|min:0',
            'amount_paid'          => 'nullable|numeric|min:0',
            'bank_details_json'    => 'nullable|array',
            'notes'                => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'items'                => 'sometimes|array|min:1',
            'items.*.description'  => 'required_with:items|string|max:255',
            'items.*.quantity'     => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price'   => 'required_with:items|numeric|min:0',
            'items.*.tax_rate'     => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($invoice, $validated) {
            $taxRateDefault = $validated['tax_rate'] ?? $invoice->tax_rate;

            if (isset($validated['items'])) {
                // Delete old items and insert updated ones
                $invoice->items()->delete();
                $subtotal = 0.0;
                $taxAmount = 0.0;

                foreach ($validated['items'] as $idx => $item) {
                    $qty = (float) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $itemTaxRate = isset($item['tax_rate']) ? (float) $item['tax_rate'] : $taxRateDefault;

                    $lineTotal = round($qty * $unitPrice, 2);
                    $lineTax = round($lineTotal * ($itemTaxRate / 100), 2);

                    $subtotal += $lineTotal;
                    $taxAmount += $lineTax;

                    $invoice->items()->create([
                        'description' => $item['description'],
                        'quantity'    => $qty,
                        'unit_price'  => $unitPrice,
                        'tax_rate'    => $itemTaxRate,
                        'tax_amount'  => $lineTax,
                        'total_price' => $lineTotal,
                        'sort_order'  => $idx + 1,
                    ]);
                }

                $validated['subtotal'] = $subtotal;
                $validated['tax_amount'] = $taxAmount;
            } else {
                $subtotal = $invoice->subtotal;
                $taxAmount = $invoice->tax_amount;
            }

            $discount = isset($validated['discount_amount']) ? (float) $validated['discount_amount'] : (float) $invoice->discount_amount;
            $totalAmount = max(0.0, round($subtotal - $discount + $taxAmount, 2));
            $amountPaid = isset($validated['amount_paid']) ? (float) $validated['amount_paid'] : (float) $invoice->amount_paid;
            $balanceDue = max(0.0, round($totalAmount - $amountPaid, 2));

            $validated['total_amount'] = $totalAmount;
            $validated['balance_due']  = $balanceDue;

            if ($balanceDue <= 0 && $totalAmount > 0) {
                $validated['status'] = 'paid';
            }

            $invoice->update($validated);

            return response()->json([
                'message' => 'Document updated successfully.',
                'invoice' => $invoice->fresh('items'),
            ]);
        });
    }

    /**
     * DELETE /api/admin/invoices/{id}
     */
    public function destroy(int $id): JsonResponse {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json(['error' => 'Document not found.'], 404);
        }

        $invoice->delete();
        return response()->json(['message' => 'Document deleted successfully.']);
    }

    /**
     * POST /api/admin/invoices/{id}/send-email
     */
    public function sendEmail(Request $request, int $id): JsonResponse {
        $invoice = Invoice::with('items')->find($id);
        if (!$invoice) {
            return response()->json(['error' => 'Document not found.'], 404);
        }

        $validated = $request->validate([
            'recipient_email' => 'required|email',
            'cc_email'        => 'nullable|email',
            'subject'         => 'nullable|string|max:200',
            'message'         => 'nullable|string',
        ]);

        $recipient = $validated['recipient_email'];
        $cc = $validated['cc_email'] ?? null;
        $subject = $validated['subject'] ?? null;
        $customMessage = $validated['message'] ?? null;

        try {
            $signature = hash_hmac('sha256', $invoice->document_number . '|' . $recipient, config('app.key'));
            $pdfDownloadUrl = url("/api/public/invoices/{$invoice->document_number}/pdf?auth={$signature}");

            $mail = new CustomerDocumentMail($invoice, $subject, $customMessage, $pdfDownloadUrl);
            $pendingMail = Mail::to($recipient);
            if ($cc) {
                $pendingMail->cc($cc);
            }
            $pendingMail->send($mail);

            $invoice->update([
                'email_sent_at'      => now(),
                'email_last_sent_to' => $recipient,
                'status'             => ($invoice->status === 'draft') ? 'sent' : $invoice->status,
            ]);

            return response()->json([
                'message'       => "Document successfully sent to {$recipient} with attached PDF.",
                'email_sent_at' => $invoice->email_sent_at->toIso8601String(),
                'status'        => $invoice->status,
            ]);
        } catch (\Throwable $e) {
            \Log::error("Failed sending document email: {$e->getMessage()}");
            return response()->json([
                'error'   => 'Failed to dispatch email.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/admin/invoices/{id}/record-payment
     */
    public function recordPayment(Request $request, int $id): JsonResponse {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return response()->json(['error' => 'Document not found.'], 404);
        }

        $validated = $request->validate([
            'payment_amount'     => 'required|numeric|min:0.01',
            'payment_date'       => 'required|date',
            'payment_method'     => 'required|string|max:50',
            'payment_reference'  => 'nullable|string|max:100',
            'convert_to_receipt' => 'nullable|boolean',
        ]);

        $paymentAmount = (float) $validated['payment_amount'];
        $newAmountPaid = round($invoice->amount_paid + $paymentAmount, 2);
        $newBalance = max(0.0, round($invoice->total_amount - $newAmountPaid, 2));

        $updateData = [
            'amount_paid'       => $newAmountPaid,
            'balance_due'       => $newBalance,
            'payment_date'      => $validated['payment_date'],
            'payment_method'    => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? $invoice->payment_reference,
            'status'            => ($newBalance <= 0) ? 'paid' : 'partially_paid',
        ];

        if (!empty($validated['convert_to_receipt']) || $newBalance <= 0) {
            $updateData['document_type'] = 'receipt';
            if (str_starts_with($invoice->document_number, 'INV-')) {
                $updateData['document_number'] = str_replace('INV-', 'REC-', $invoice->document_number);
            }
        }

        $invoice->update($updateData);

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'invoice' => $invoice->fresh('items'),
        ]);
    }

    /**
     * GET /api/admin/invoices/{id}/pdf
     * Direct binary PDF download/stream
     */
    public function pdf(int $id): Response {
        $invoice = Invoice::with('items')->findOrFail($id);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        $html = view('pdf.document_pdf', ['invoice' => $invoice])->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$invoice->document_number}.pdf\"",
        ]);
    }

    /**
     * POST /api/admin/invoices/preview-pdf
     * Direct binary vector PDF generation for unsaved / live draft documents
     */
    public function previewPdf(Request $request): Response {
        $validated = $request->validate([
            'document_type'        => 'nullable|in:invoice,receipt',
            'document_number'      => 'nullable|string',
            'quote_request_id'     => 'nullable|integer',
            'customer_name'        => 'nullable|string',
            'customer_email'       => 'nullable|string',
            'customer_phone'       => 'nullable|string',
            'customer_company'     => 'nullable|string',
            'billing_address'      => 'nullable|string',
            'service_address'      => 'nullable|string',
            'service_type'         => 'nullable|string',
            'work_status'          => 'nullable|in:work_to_be_done,work_done',
            'work_scheduled_date'  => 'nullable|string',
            'work_completed_date'  => 'nullable|string',
            'issue_date'           => 'nullable|string',
            'due_date'             => 'nullable|string',
            'payment_date'         => 'nullable|string',
            'payment_method'       => 'nullable|string',
            'payment_reference'    => 'nullable|string',
            'status'               => 'nullable|string',
            'currency'             => 'nullable|string',
            'subtotal'             => 'nullable|numeric',
            'discount_amount'      => 'nullable|numeric',
            'tax_rate'             => 'nullable|numeric',
            'tax_amount'           => 'nullable|numeric',
            'total_amount'         => 'nullable|numeric',
            'amount_paid'          => 'nullable|numeric',
            'balance_due'          => 'nullable|numeric',
            'bank_details_json'    => 'nullable|array',
            'notes'                => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'items'                => 'nullable|array',
        ]);

        $invoice = new Invoice($validated);
        $itemsCollection = collect();
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $itemData) {
                $itemsCollection->push(new InvoiceItem($itemData));
            }
        } else {
            $itemsCollection->push(new InvoiceItem([
                'description' => $validated['service_type'] ?? 'Specialist Service (Standard Scope)',
                'quantity'    => 1,
                'unit_price'  => $validated['subtotal'] ?? 0,
                'tax_rate'    => $validated['tax_rate'] ?? 20,
                'tax_amount'  => $validated['tax_amount'] ?? 0,
                'total_price' => $validated['subtotal'] ?? 0,
                'sort_order'  => 1,
            ]));
        }
        $invoice->setRelation('items', $itemsCollection);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        $html = view('pdf.document_pdf', ['invoice' => $invoice])->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = ($invoice->document_number ?: 'draft_document') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * GET /api/public/invoices/{documentNumber}/pdf
     * Secure public customer PDF download
     */
    public function publicPdf(Request $request, string $documentNumber): Response {
        $invoice = Invoice::with('items')->where('document_number', $documentNumber)->firstOrFail();

        $auth = $request->query('auth');
        if ($auth) {
            $expected = hash_hmac('sha256', $invoice->document_number . '|' . $invoice->customer_email, config('app.key'));
            $expectedLast = $invoice->email_last_sent_to ? hash_hmac('sha256', $invoice->document_number . '|' . $invoice->email_last_sent_to, config('app.key')) : null;
            if (!hash_equals($expected, $auth) && (!$expectedLast || !hash_equals($expectedLast, $auth))) {
                abort(403, 'Invalid document access signature.');
            }
        }

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        $html = view('pdf.document_pdf', ['invoice' => $invoice])->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$invoice->document_number}.pdf\"",
        ]);
    }
}
