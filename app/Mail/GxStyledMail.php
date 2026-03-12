<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GxStyledMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $heading;
    public string $body;
    public ?string $ctaText;
    public ?string $ctaUrl;

    public function __construct(
        string $subject,
        string $heading,
        string $body,
        ?string $ctaText = null,
        ?string $ctaUrl = null,
    ) {
        $this->emailSubject = $subject;
        $this->heading = $heading;
        $this->body = $body;
        $this->ctaText = $ctaText;
        $this->ctaUrl = $ctaUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.gx-styled');
    }
}
