<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice->document_type === 'receipt' ? 'Payment Receipt' : 'Invoice' }} - {{ $invoice->document_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 30px 15px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #0f172a;
            padding: 32px 24px;
            text-align: center;
            border-bottom: 4px solid {{ $invoice->document_type === 'receipt' ? '#059669' : '#d97706' }};
        }
        .header h1 {
            color: #ffffff;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin: 0;
        }
        .header p {
            color: #94a3b8;
            font-size: 12px;
            margin: 6px 0 0 0;
            font-weight: 500;
        }
        .content {
            padding: 32px 28px;
        }
        .badge {
            display: inline-block;
            padding: 6px 14px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background-color: {{ $invoice->document_type === 'receipt' ? '#d1fae5' : '#fef3c7' }};
            color: {{ $invoice->document_type === 'receipt' ? '#065f46' : '#92400e' }};
            border-radius: 9999px;
            margin-bottom: 18px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .intro-text {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .custom-note {
            background-color: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 14px 16px;
            border-radius: 0 8px 8px 0;
            font-size: 13px;
            color: #334155;
            font-style: italic;
            margin-bottom: 24px;
        }
        .summary-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .summary-row {
            display: table;
            width: 100%;
            padding: 6px 0;
            border-bottom: 1px solid #edf2f7;
            font-size: 13px;
        }
        .summary-row:last-child {
            border-bottom: none;
        }
        .summary-label {
            display: table-cell;
            color: #64748b;
            font-weight: 500;
            width: 45%;
        }
        .summary-value {
            display: table-cell;
            color: #0f172a;
            font-weight: 700;
            text-align: right;
            width: 55%;
        }
        .highlight-balance {
            font-size: 16px;
            color: {{ ($invoice->status === 'paid' || $invoice->document_type === 'receipt' || $invoice->balance_due <= 0) ? '#059669' : '#d97706' }};
            font-weight: 800;
        }
        .bank-details {
            background-color: #0f172a;
            color: #f8fafc;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 24px;
            font-size: 12px;
            line-height: 1.6;
        }
        .bank-details h4 {
            margin: 0 0 8px 0;
            color: #f59e0b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .items-preview {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 24px;
        }
        .items-preview th {
            text-align: left;
            padding: 8px 10px;
            background-color: #f1f5f9;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .items-preview td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        .pdf-pill {
            display: inline-block;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 600;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <h1>Premium Expert Services</h1>
                <p>Commercial & Domestic Property Specialists</p>
            </div>

            <!-- Main Content -->
            <div class="content">
                <span class="badge">
                    {{ $invoice->document_type === 'receipt' ? 'Official Payment Receipt' : 'Invoice Notification' }}
                </span>

                <div class="greeting">
                    Dear {{ $invoice->customer_name }},
                </div>

                <div class="intro-text">
                    @if($invoice->document_type === 'receipt' || $invoice->status === 'paid' || $invoice->balance_due <= 0)
                        Thank you for your payment. Please find attached the official receipt for your records regarding the services provided by Premium Expert Services.
                    @elseif($invoice->work_status === 'work_to_be_done')
                        Please find attached your invoice for the upcoming scheduled services. You can review the line items and payment instructions below.
                    @else
                        Please find attached your invoice for the completed services. We appreciate your prompt payment according to the agreed terms.
                    @endif
                </div>

                @if(!empty($customMessage))
                    <div class="custom-note">
                        &ldquo;{{ $customMessage }}&rdquo;
                    </div>
                @endif

                <!-- Document Summary Card -->
                <div class="summary-card">
                    <div class="summary-row">
                        <span class="summary-label">Document Number</span>
                        <span class="summary-value">{{ $invoice->document_number }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Service</span>
                        <span class="summary-value">{{ $invoice->service_type }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Work Stage</span>
                        <span class="summary-value">
                            {{ $invoice->work_status === 'work_done' ? 'Completed & Certified' : 'Work to be Completed' }}
                        </span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Issue Date</span>
                        <span class="summary-value">{{ $invoice->issue_date ? $invoice->issue_date->format('d M Y') : date('d M Y') }}</span>
                    </div>
                    @if($invoice->document_type === 'receipt' && $invoice->payment_date)
                        <div class="summary-row">
                            <span class="summary-label">Date Paid</span>
                            <span class="summary-value" style="color: #059669;">{{ $invoice->payment_date->format('d M Y') }}</span>
                        </div>
                    @elseif($invoice->due_date)
                        <div class="summary-row">
                            <span class="summary-label">Payment Due</span>
                            <span class="summary-value" style="color: #dc2626;">{{ $invoice->due_date->format('d M Y') }}</span>
                        </div>
                    @endif
                    <div class="summary-row">
                        <span class="summary-label">Total Amount</span>
                        <span class="summary-value">&pound;{{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">{{ ($invoice->status === 'paid' || $invoice->document_type === 'receipt' || $invoice->balance_due <= 0) ? 'Status' : 'Balance Due' }}</span>
                        <span class="summary-value highlight-balance">
                            {{ ($invoice->status === 'paid' || $invoice->document_type === 'receipt' || $invoice->balance_due <= 0) ? 'PAID IN FULL' : '£' . number_format($invoice->balance_due, 2) }}
                        </span>
                    </div>
                </div>

                <!-- Scope of Work Table -->
                <table class="items-preview">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="text-align: center; width: 40px;">Qty</th>
                            <th style="text-align: right; width: 70px;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td style="text-align: center;">{{ $item->quantity }}</td>
                                <td style="text-align: right; font-weight: 600;">&pound;{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td>{{ $invoice->service_type }} (Standard Scope)</td>
                                <td style="text-align: center;">1</td>
                                <td style="text-align: right; font-weight: 600;">&pound;{{ number_format($invoice->total_amount, 2) }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Bank details if payment is due -->
                @if($invoice->status !== 'paid' && $invoice->document_type !== 'receipt' && $invoice->balance_due > 0)
                    @php
                        $bank = $invoice->bank_details_json ?: [
                            'bank_name' => 'Barclays Bank UK',
                            'account_name' => 'Premium Expert Services Ltd',
                            'sort_code' => '20-00-00',
                            'account_number' => '87654321',
                        ];
                    @endphp
                    <div class="bank-details">
                        <h4>Bank Transfer Instructions (BACS)</h4>
                        Bank: <strong>{{ $bank['bank_name'] ?? 'Barclays Bank UK' }}</strong><br>
                        Account Name: <strong>{{ $bank['account_name'] ?? 'Premium Expert Services Ltd' }}</strong><br>
                        Sort Code: <strong>{{ $bank['sort_code'] ?? '20-00-00' }}</strong><br>
                        Account Number: <strong>{{ $bank['account_number'] ?? '87654321' }}</strong><br>
                        Payment Reference: <strong style="color: #f59e0b;">{{ $invoice->document_number }}</strong>
                    </div>
                @endif

                <div class="pdf-pill">
                    &#128206; A complete, printable PDF copy (<strong>{{ $invoice->document_number }}.pdf</strong>) has been attached to this email.
                </div>

                <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0;">
                    If you have any questions or require modifications to your booking, please reply to this email or call our team at <strong>0800 123 4567</strong>.
                </p>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p style="margin: 0 0 6px 0; font-weight: 600; color: #64748b;">
                    Premium Expert Services Ltd &bull; Registered in England &amp; Wales #14829104
                </p>
                <p style="margin: 0;">
                    Suite 402, Enterprise House, Business Way, London &bull; VAT GB 432 9812 04
                </p>
            </div>
        </div>
    </div>
</body>
</html>
