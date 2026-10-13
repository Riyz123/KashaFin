<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WeeklySummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public array $summary,
        public Collection $byCategory,
        public Carbon $periodFrom,
        public Carbon $periodTo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu resumen semanal de KashaFin',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.weekly-summary');
    }
}
