<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanRequest;
use App\Models\Loan;
use App\Notifications\LoanConfirmationNotification;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $sort = in_array($request->query('sort'), ['date', 'amount'], true) ? $request->query('sort') : 'date';
        $dir = $request->filled('sort') && $request->query('dir') !== 'desc' ? 'asc' : 'desc';

        $loans = $user->loans()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('person_name', 'like', '%'.$request->string('search').'%');
            })
            ->when($request->filled('direction'), function ($query) use ($request) {
                $query->where('direction', $request->string('direction'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->orderBy($sort, $dir)
            ->get();

        // Loans someone else created, linking to me, that I haven't
        // approved or rejected yet — these live on the creator's account,
        // not mine, until I act on them.
        $pendingConfirmations = Loan::query()
            ->where('person_user_id', $user->id)
            ->where('status', 'pendiente_confirmacion')
            ->with('user')
            ->latest('date')
            ->get();

        // My own "prestado" rows where the debtor already reported paying
        // — I need to confirm I actually received it before it settles.
        $paymentsToConfirm = $loans->where('status', 'pago_pendiente_confirmacion')
            ->where('direction', 'prestado')
            ->values();

        $totals = [
            'owed_to_me' => (float) $loans->where('direction', 'prestado')->where('status', 'pendiente')->sum('amount'),
            'i_owe' => (float) $loans->where('direction', 'recibido')->where('status', 'pendiente')->sum('amount'),
        ];

        return view('loans.index', [
            'loans' => $loans,
            'totals' => $totals,
            'pendingConfirmations' => $pendingConfirmations,
            'paymentsToConfirm' => $paymentsToConfirm,
            'search' => $request->string('search')->toString(),
            'direction' => $request->string('direction')->toString(),
            'status' => $request->string('status')->toString(),
        ]);
    }

    public function create()
    {
        return view('loans.create');
    }

    public function store(StoreLoanRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        if (! empty($data['person_user_id'])) {
            $data['status'] = 'pendiente_confirmacion';
        }

        $loan = Loan::create($data);

        if ($loan->person_user_id) {
            $loan->personUser->notify(new LoanConfirmationNotification($loan));
        }

        $status = $loan->person_user_id
            ? 'Préstamo registrado. Se notificó a la otra persona para que lo confirme.'
            : 'Préstamo registrado correctamente.';

        return redirect()->route('loans.index')->with('status', $status);
    }

    public function edit(Loan $loan)
    {
        $this->authorizeOwnership($loan);

        return view('loans.edit', ['loan' => $loan]);
    }

    public function update(StoreLoanRequest $request, Loan $loan)
    {
        $this->authorizeOwnership($loan);

        $loan->update($request->validated());

        return redirect()->route('loans.index')->with('status', 'Préstamo actualizado correctamente.');
    }

    public function destroy(Loan $loan)
    {
        $this->authorizeOwnership($loan);

        $loan->delete();

        return redirect()->route('loans.index')->with('status', 'Préstamo eliminado correctamente.');
    }

    public function markPaid(Loan $loan)
    {
        $this->authorizeOwnership($loan);

        $loan->reportPayment();

        $status = $loan->person_user_id
            ? 'Reportaste el pago. Se notificó a la otra persona para que lo confirme.'
            : 'Préstamo marcado como pagado.';

        return redirect()->route('loans.index')->with('status', $status);
    }

    /**
     * Acted on by the invited account (person_user_id), not the creator —
     * the loan only becomes real on both sides once they say so.
     */
    public function approve(Request $request, Loan $loan)
    {
        abort_unless($loan->person_user_id === $request->user()->id, 403);
        abort_unless($loan->isPendingConfirmation(), 422, 'Esta confirmación ya fue respondida.');

        $loan->approve();

        return back()->with('status', 'Préstamo confirmado.');
    }

    public function reject(Request $request, Loan $loan)
    {
        abort_unless($loan->person_user_id === $request->user()->id, 403);
        abort_unless($loan->isPendingConfirmation(), 422, 'Esta confirmación ya fue respondida.');

        $loan->reject();

        return back()->with('status', 'Préstamo rechazado.');
    }

    public function confirmPayment(Loan $loan)
    {
        $this->authorizeOwnership($loan);
        abort_unless($loan->isPendingPaymentConfirmation(), 422, 'No hay un pago pendiente de confirmar.');

        $loan->confirmPayment();

        return back()->with('status', 'Pago confirmado.');
    }

    public function rejectPaymentConfirmation(Loan $loan)
    {
        $this->authorizeOwnership($loan);
        abort_unless($loan->isPendingPaymentConfirmation(), 422, 'No hay un pago pendiente de confirmar.');

        $loan->rejectPaymentConfirmation();

        return back()->with('status', 'Se rechazó la confirmación de pago; el préstamo sigue pendiente.');
    }

    private function authorizeOwnership(Loan $loan): void
    {
        abort_unless($loan->user_id === auth()->id(), 403);
    }
}
