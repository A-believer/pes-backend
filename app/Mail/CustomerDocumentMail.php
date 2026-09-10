<?php

namespace App\Mail;

use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerDocumentMail extends Mailable {
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public ?string $customSubject;
    public ?string $customMessage;
    public ?string $pdfDownloadUrl;
    protected ?string $pdfContent = null;

    public function __construct(
        Invoice $invoice,
        ?string $customSubject = null,
        ?string $customMessage = null,
        ?string $pdfDownloadUrl = null
    ) {
        $this->invoice = $invoice;
        $this->customSubject = $customSubject;
        $this->customMessage = $customMessage;
        $this->pdfDownloadUrl = $pdfDownloadUrl;

        // Ensure relations are preloaded
        $this->invoice->loadMissing('items');

        // Pre-generate PDF content
        $this->generatePdf();
    }

    public function generatePdf(): void {
        try {
            $this->invoice->loadMissing('items');

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Helvetica');
            $dompdf = new Dompdf($options);

            $html = view('pdf.document_pdf', ['invoice' => $this->invoice])->render();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $this->pdfContent = $dompdf->output();
        } catch (\Throwable $e) {
            \Log::error("PDF generation failed in CustomerDocumentMail for invoice {$this->invoice->document_number}: " . $e->getMessage());
            throw new \RuntimeException("Unable to generate PDF attachment: " . $e->getMessage(), 0, $e);
        }
    }

    public function getPdfContent(): string {
        if (empty($this->pdfContent)) {
            $this->generatePdf();
        }

        return $this->pdfContent;
    }

    public function envelope(): Envelope {
        $typeLabel = $this->invoice->document_type === 'receipt' ? 'Payment Receipt' : 'Invoice';
        $defaultSubject = "{$typeLabel} {$this->invoice->document_number} - Premium Expert Services";

        return new Envelope(
            subject: $this->customSubject ?: $defaultSubject,
        );
    }

    public function content(): Content {
        return new Content(
            view: 'emails.customer_document',
            with: [
                'invoice'        => $this->invoice,
                'customMessage'  => $this->customMessage,
                'pdfDownloadUrl' => $this->pdfDownloadUrl,
            ],
        );
    }

    public function attachments(): array {
        return [
            Attachment::fromData(fn () => $this->getPdfContent(), "{$this->invoice->document_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
