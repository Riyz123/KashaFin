<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent both when a dean approves a self-registration request and when a
 * dean/admin bulk-imports students via CSV — either way, the account's real
 * password only ever exists as plain text for the moment this mail renders.
 * Deliberately NOT queued: shared hosting (InfinityFree) has no worker
 * process to run `queue:work`, so a queued mail would just sit in the
 * `jobs` table forever and never actually send.
 */
class TemporaryPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu cuenta de KashaFin ya está lista',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.temporary-password',
        );
    }
}
