<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Logs activity (created + the accept/reject update) so that whoever was
 * paid has a durable record that it happened — `activity_log` doesn't have
 * a foreign key back to `loans`/`loan_amortizations`, so this entry survives
 * even if the loan it belonged to gets deleted afterward. `deleted` is
 * skipped because a loan's cascade delete removes these rows at the
 * database level (FK ON DELETE CASCADE), which never fires Eloquent's
 * `deleted` event anyway — nothing to log there.
 */
class LoanAmortization extends Model
{
    use LogsActivity;

    protected static $doNotRecordEvents = ['deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('loan_amortization')
            ->setDescriptionForEvent(function (string $event) {
                $amount = number_format((float) $this->amount, 2);
                $person = $this->loan->person_name;

                return match (true) {
                    $event === 'created' && $this->status === 'aceptado' => "Registró un pago de S/ {$amount} a {$person}",
                    $event === 'created' => "Solicitó amortizar S/ {$amount} a {$person}",
                    $this->status === 'aceptado' => "{$person} aceptó el pago de S/ {$amount}",
                    $this->status === 'rechazado' => "{$person} rechazó el pago de S/ {$amount}",
                    default => "Actualizó una amortización de S/ {$amount} con {$person}",
                };
            });
    }

    protected $fillable = [
        'loan_id', 'amount', 'status', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'resolved_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }
}
