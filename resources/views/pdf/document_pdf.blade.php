<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->document_number }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .logo-box {
            font-size: 18px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .tagline {
            font-size: 9px;
            font-weight: bold;
            color: #d97706;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }
        .company-meta {
            font-size: 9px;
            color: #64748b;
            margin-top: 5px;
            line-height: 1.3;
        }
        .doc-title {
            font-size: 22px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: right;
            color: {{ $invoice->document_type === 'receipt' ? '#047857' : '#0f172a' }};
        }
        .doc-num {
            font-size: 12px;
            font-weight: bold;
            color: #334155;
            text-align: right;
            margin-top: 2px;
        }
        .doc-meta {
            font-size: 9px;
            color: #475569;
            text-align: right;
            margin-top: 6px;
            line-height: 1.4;
        }
        .work-status-banner {
            background-color: {{ $invoice->work_status === 'work_done' ? '#ecfdf5' : '#fffbeb' }};
            border: 1px solid {{ $invoice->work_status === 'work_done' ? '#a7f3d0' : '#fde68a' }};
            color: {{ $invoice->work_status === 'work_done' ? '#065f46' : '#92400e' }};
            padding: 8px 12px;
            font-weight: bold;
            font-size: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .parties-table {
            width: 100%;
            margin-bottom: 18px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .parties-table td {
            padding: 10px 14px;
            vertical-align: top;
            width: 50%;
        }
        .section-label {
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .client-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 10px;
            text-align: left;
        }
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .totals-table td {
            padding: 5px 10px;
            font-size: 10px;
        }
        .total-row {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            border-top: 1px solid #cbd5e1;
        }
        .balance-row {
            background-color: {{ ($invoice->status === 'paid' || $invoice->document_type === 'receipt' || $invoice->balance_due <= 0) ? '#d1fae5' : '#fef3c7' }};
            font-weight: bold;
            font-size: 11px;
            color: {{ ($invoice->status === 'paid' || $invoice->document_type === 'receipt' || $invoice->balance_due <= 0) ? '#065f46' : '#78350f' }};
        }
        .bank-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px;
            border-radius: 6px;
            font-size: 9px;
            line-height: 1.4;
            color: #334155;
        }
        .paid-stamp {
            border: 2px dashed #059669;
            background-color: #ecfdf5;
            color: #065f46;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="vertical-align: top; width: 60%;">
                <div class="logo-box">Premium Expert Services</div>
                <div class="tagline">Domestic & Commercial Specialists</div>
                <div class="company-meta">
                    Suite 402, Enterprise House, Business Way, London, UK<br>
                    Reg: 14829104 &bull; VAT ID: GB 432 9812 04<br>
                    Tel: 0800 123 4567 &bull; billing@expets.co.uk &bull; https://expets.co.uk
                </div>
            </td>
            <td style="vertical-align: top; width: 40%;">
                <div class="doc-title">{{ $invoice->document_type === 'receipt' ? 'Official Receipt' : 'Invoice' }}</div>
                <div class="doc-num">#{{ $invoice->document_number }}</div>
                <div class="doc-meta">
                    <strong>Date Issued:</strong> {{ $invoice->issue_date ? $invoice->issue_date->format('d M Y') : date('d M Y') }}<br>
                    @if($invoice->document_type === 'receipt' && $invoice->payment_date)
                        <strong>Date Paid:</strong> {{ $invoice->payment_date->format('d M Y') }}<br>
                    @elseif($invoice->due_date)
                        <strong>Payment Due:</strong> {{ $invoice->due_date->format('d M Y') }}<br>
                    @endif
                    @if($invoice->payment_method)
                        <strong>Method:</strong> {{ $invoice->payment_method }}<br>
                    @endif
                    <strong>Status:</strong> {{ strtoupper(str_replace('_', ' ', $invoice->status)) }}
                </div>
            </td>
        </tr>
    </table>

    <!-- WORK STATUS BANNER -->
    <div class="work-status-banner">
        @if($invoice->work_status === 'work_done')
            <strong>WORK COMPLETED &amp; CERTIFIED</strong>
            @if($invoice->work_completed_date)
                &mdash; Completion Signed Off: {{ $invoice->work_completed_date->format('d M Y') }}
            @endif
        @else
            <strong>WORK TO BE COMPLETED</strong>
            @if($invoice->work_scheduled_date)
                &mdash; Scheduled Execution: {{ $invoice->work_scheduled_date->format('d M Y') }}
            @else
                &mdash; Scheduled date to be finalized upon deposit
            @endif
        @endif
    </div>

    <!-- PARTIES & JOB DETAILS -->
    <table class="parties-table" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="section-label">Billed To / Customer</div>
                <div class="client-name">{{ $invoice->customer_name }}</div>
                @if($invoice->customer_company)
                    <div style="font-weight: bold; color: #475569; font-size: 10px;">{{ $invoice->customer_company }}</div>
                @endif
                <div style="margin-top: 3px; color: #475569; font-size: 9px; line-height: 1.3;">
                    {!! nl2br(e($invoice->billing_address)) !!}
                </div>
                <div style="margin-top: 4px; color: #64748b; font-size: 9px;">
                    Email: {{ $invoice->customer_email }}<br>
                    @if($invoice->customer_phone) Phone: {{ $invoice->customer_phone }} @endif
                </div>
            </td>
            <td>
                <div class="section-label">Job &amp; Service Details</div>
                <div style="font-size: 11px; font-weight: bold; color: #0f172a;">{{ $invoice->service_type }}</div>
                <div style="margin-top: 3px; color: #475569; font-size: 9px; line-height: 1.3;">
                    <strong>Service Location:</strong> {{ $invoice->service_address ?: $invoice->billing_address }}
                </div>
                @if($invoice->quote_request_id)
                    <div style="margin-top: 4px; color: #64748b; font-size: 9px;">
                        Quote Reference: <strong>QR-{{ $invoice->quote_request_id }}</strong>
                    </div>
                @endif
                @if($invoice->payment_reference)
                    <div style="margin-top: 2px; color: #64748b; font-size: 9px;">
                        Payment Reference: <strong>{{ $invoice->payment_reference }}</strong>
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <!-- LINE ITEMS -->
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 25px;">#</th>
                <th>Description / Scope of Work</th>
                <th class="text-center" style="width: 40px;">Qty</th>
                <th class="text-right" style="width: 65px;">Rate</th>
                <th class="text-right" style="width: 45px;">VAT</th>
                <th class="text-right" style="width: 75px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $idx => $item)
                <tr>
                    <td class="text-center" style="color: #94a3b8;">{{ $idx + 1 }}</td>
                    <td><strong>{{ $item->description }}</strong></td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">&pound;{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ $item->tax_rate }}%</td>
                    <td class="text-right" style="font-weight: bold;">&pound;{{ number_format($item->total_price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-center">1</td>
                    <td>Standard Specialist Service</td>
                    <td class="text-center">1</td>
                    <td class="text-right">&pound;{{ number_format($invoice->subtotal, 2) }}</td>
                    <td class="text-right">{{ $invoice->tax_rate }}%</td>
                    <td class="text-right" style="font-weight: bold;">&pound;{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TOTALS & PAYMENT SECTION -->
    <table style="width: 100%;" cellpadding="0" cellspacing="0">
        <tr>
            <!-- Left: Payment Instructions or Paid Seal -->
            <td style="width: 55%; vertical-align: top; padding-right: 15px;">
                @if($invoice->status === 'paid' || $invoice->document_type === 'receipt' || $invoice->balance_due <= 0)
                    <div class="paid-stamp">
                        <div style="font-size: 13px; font-weight: 900; letter-spacing: 1px;">PAID IN FULL &bull; OFFICIAL RECEIPT</div>
                        <div style="font-size: 9px; margin-top: 3px;">
                            Payment verified on {{ $invoice->payment_date ? $invoice->payment_date->format('d M Y') : date('d M Y') }}. Thank you for choosing Premium Expert Services.
                        </div>
                    </div>
                @else
                    @php
                        $bank = $invoice->bank_details_json ?: [
                            'bank_name' => 'Barclays Bank UK',
                            'account_name' => 'Premium Expert Services Ltd',
                            'sort_code' => '20-00-00',
                            'account_number' => '87654321',
                        ];
                    @endphp
                    <div class="bank-box">
                        <strong style="font-size: 10px; color: #0f172a; text-transform: uppercase;">Bank Transfer Details (BACS)</strong><br>
                        Bank: {{ $bank['bank_name'] ?? 'Barclays Bank UK' }}<br>
                        Account Name: {{ $bank['account_name'] ?? 'Premium Expert Services Ltd' }}<br>
                        Sort Code: <strong>{{ $bank['sort_code'] ?? '20-00-00' }}</strong> &bull; Account No: <strong>{{ $bank['account_number'] ?? '87654321' }}</strong><br>
                        Reference: <strong style="color: #b45309;">{{ $invoice->document_number }}</strong>
                    </div>
                @endif

                <div style="margin-top: 10px; font-size: 8px; color: #64748b; line-height: 1.3;">
                    <strong>Terms &amp; Guarantee:</strong> {{ $invoice->terms_and_conditions ?: 'All work completed according to British standards and backed by our 100% Satisfaction Guarantee. Payment is due as stated.' }}
                </div>
                @if($invoice->notes)
                    <div style="margin-top: 6px; font-size: 8px; color: #475569; font-style: italic;">
                        Notes: &ldquo;{{ $invoice->notes }}&rdquo;
                    </div>
                @endif
            </td>

            <!-- Right: Totals Breakdown -->
            <td style="width: 45%; vertical-align: top;">
                <table class="totals-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>Subtotal</td>
                        <td class="text-right">&pound;{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                        <tr style="color: #059669;">
                            <td>Discount Applied</td>
                            <td class="text-right">-&pound;{{ number_format($invoice->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>VAT ({{ $invoice->tax_rate }}%)</td>
                        <td class="text-right">&pound;{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td style="padding-top: 8px;">Total Amount</td>
                        <td class="text-right" style="padding-top: 8px; font-size: 13px;">&pound;{{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; font-size: 9px;">Amount Paid / Deposit</td>
                        <td class="text-right" style="color: #047857; font-weight: bold;">&pound;{{ number_format($invoice->amount_paid, 2) }}</td>
                    </tr>
                    <tr class="balance-row">
                        <td style="padding: 7px 10px;">{{ ($invoice->status === 'paid' || $invoice->document_type === 'receipt' || $invoice->balance_due <= 0) ? 'Balance Settled' : 'BALANCE DUE' }}</td>
                        <td class="text-right" style="padding: 7px 10px; font-size: 12px;">&pound;{{ number_format($invoice->balance_due, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        Premium Expert Services Ltd &bull; Registered in England &amp; Wales &bull; Company No. 14829104 &bull; Page 1 of 1
    </div>
</body>
</html>
