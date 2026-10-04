<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A simple, reusable transactional email used across the platform
 * (task assignments, milestones, activities, roles, registration).
 */
class SystemMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $lines
     */
    public function __construct(
        public string $subjectLine,
        public string $heading,
        public array $lines = [],
        public ?string $actionUrl = null,
        public string $actionLabel = 'Open',
        public string $greeting = 'Hello',
        public ?string $trackingUrl = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.system');
    }
}
