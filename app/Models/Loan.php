<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * F014: informal tracking of money lent to or borrowed from another
 * person. The other side can optionally be linked to a real KashaFin
 * account (person_user_id, searched the same way as shared-expense
 * participants), but doesn't have to be — an external person or entity
 * (a store, a bank, a family member) is just a free-text person_name with
 * no linked account. Deliberately NOT wired into LiquidityProjectionService:
 * these are debts between people, not the student's own income/expense, so
 * they stay out of the balance projection math.
 *
 * When linked to a real account, both the loan's creation AND its payment
 * go through a confirmation step by the other side (same spirit as
 * SharedExpenseParticipant's accept/reject) instead of one person being
 * able to unilaterally declare "you owe me" or "I already paid": on
 * approval, a mirrored Loan row is created on the other account
 * (counterpart_loan_id links the pair, opposite direction); reporting a
 * payment notifies the counterpart, who must confirm or reject it before
 * either side flips to "pagado". An unlinked (external person) loan skips
 * all of this — there's no account to confirm with, so it behaves exactly
 * as before: immediate creation, immediate "mark as paid".
 */
class Loan extends Model
{
    use LogsActivity;

    // Préstamos ya no se pueden editar (solo crear o eliminar), así que
    // esto cubre el 100% de lo que le puede pasar a uno — igual se excluye
    // 'updated' explícitamente por si algún cambio de estado interno
    // (aprobar, rechazar, confirmar pago) llegara a tocar el modelo.
    protected static $doNotRecordEvents = ['updated'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('loan')
            ->setDescriptionForEvent(fn (string $event) => ($event === 'created' ? 'Registró' : 'Eliminó').
                ' un préstamo '.($this->direction === 'prestado' ? 'a' : 'de').
                " {$this->person_name} por S/ ".number_format((float) $this->amount, 2));
    }

    protected $fillable = [
        'user_id', 'person_name', 'person_user_id', 'direction', 'amount', 'date', 'due_date', 'status', 'notes', 'counterpart_loan_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'person_user_id');
    }

    public function counterpart(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'counterpart_loan_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }

    public function isPendingConfirmation(): bool
    {
        return $this->status === 'pendiente_confirmacion';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rechazado';
    }

    public function isPendingPaymentConfirmation(): bool
    {
        return $this->status === 'pago_pendiente_confirmacion';
    }

    public function isPaid(): bool
    {
        return $this->status === 'pagado';
    }

    /**
     * The invited account confirms the loan is real — only now does a
     * mirrored row appear on their side (opposite direction) so it shows up
     * in their own list exactly like a loan they created themselves.
     */
    public function approve(): void
    {
        $mirror = self::create([
            'user_id' => $this->person_user_id,
            'person_name' => $this->user->name,
            'person_user_id' => $this->user_id,
            'direction' => $this->direction === 'prestado' ? 'recibido' : 'prestado',
            'amount' => $this->amount,
            'date' => $this->date,
            'due_date' => $this->due_date,
            'status' => 'pendiente',
            'notes' => $this->notes,
        ]);

        $this->update(['status' => 'pendiente', 'counterpart_loan_id' => $mirror->id]);
        $mirror->update(['counterpart_loan_id' => $this->id]);
    }

    /**
     * Kept in the database (not deleted) purely for traceability — it just
     * never counts toward anything and never gets a mirror.
     */
    public function reject(): void
    {
        $this->update(['status' => 'rechazado']);
    }

    /**
     * Unlinked (external person) loans mark paid immediately, same as
     * always. A linked loan instead asks the counterpart to confirm before
     * either side becomes "pagado".
     */
    public function reportPayment(): void
    {
        if (! $this->person_user_id) {
            $this->markPaid();

            return;
        }

        $this->update(['status' => 'pago_pendiente_confirmacion']);
        $this->counterpart?->update(['status' => 'pago_pendiente_confirmacion']);
    }

    public function confirmPayment(): void
    {
        $this->update(['status' => 'pagado']);
        $this->counterpart?->update(['status' => 'pagado']);
    }

    /**
     * The debtor can report again afterward — this just undoes the
     * "pending confirmation" state back to a normal pending loan.
     */
    public function rejectPaymentConfirmation(): void
    {
        $this->update(['status' => 'pendiente']);
        $this->counterpart?->update(['status' => 'pendiente']);
    }

    public function markPaid(): void
    {
        $this->update(['status' => 'pagado']);
    }
}
