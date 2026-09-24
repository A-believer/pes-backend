<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CareerApplicationMail extends Mailable {
    use Queueable, SerializesModels;

    public Submission $submission;

    public function __construct(Submission $submission) {
        $this->submission = $submission;
    }

    public function envelope(): Envelope {
        return new Envelope(
            subject: 'New Job Application: ' . ($this->submission->service ?? 'Cleaning Role') . ' - ' . $this->submission->name,
        );
    }

    public function content(): Content {
        return new Content(
            view: 'emails.career',
        );
    }

    public function attachments(): array {
        $attachments = [];

        if ($this->submission->cv_path && Storage::disk('public')->exists($this->submission->cv_path)) {
            $attachments[] = Attachment::fromStorageDisk(
                'public',
                $this->submission->cv_path
            )->as($this->submission->cv_original_name ?? 'CV.pdf');
        }

        return $attachments;
    }
}
