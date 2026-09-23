<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowLiquidityAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public float $projectedBalance,
        public float $threshold,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Alerta: riesgo de iliquidez en KashaFin',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-liquidity',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
