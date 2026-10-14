<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Not queued — InfinityFree has no worker process to run `queue:work`, a
 * queued mail would just sit in the `jobs` table forever (same reason
 * TemporaryPasswordMail isn't queued either).
 */
class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código para recuperar tu contraseña de KashaFin',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-code',
            text: 'emails.password-reset-code-text',
        );
    }
}
