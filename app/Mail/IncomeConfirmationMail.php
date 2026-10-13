<?php

namespace App\Mail;

use App\Models\Income;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncomeConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Income $income,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'KashaFin: confirma tu ingreso fijo',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.income-confirmation');
    }
}
