<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InactivityReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $daysInactive,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'KashaFin: ¿se te pasó registrar algo?',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.inactivity-reminder');
    }
}
