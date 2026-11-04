<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanAmortizationRequest;
use App\Http\Requests\StoreLoanRequest;
use App\Models\Loan;
use App\Models\LoanAmortization;
use App\Services\LoanCreationService;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(private LoanCreationService $loanCreation) {}

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

        // Amortization requests waiting on ME to accept/decline — I'm the
        // creditor, so they live on the debtor's loan row, which is MY
        // "prestado" row's counterpart.
        $creditorCounterpartIds = $loans->where('direction', 'prestado')->pluck('counterpart_loan_id')->filter();
        $amortizationRequests = LoanAmortization::query()
            ->whereIn('loan_id', $creditorCounterpartIds)
            ->where('status', 'pendiente')
            ->with('loan.user')
            ->latest()
            ->get();

        return view('loans.index', [
            'loans' => $loans,
            'totals' => $totals,
            'pendingConfirmations' => $pendingConfirmations,
            'paymentsToConfirm' => $paymentsToConfirm,
            'amortizationRequests' => $amortizationRequests,
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

        $loan = $this->loanCreation->create($data);

        return redirect()->route('loans.index')->with('status', $this->loanCreation->statusMessage($loan));
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

    /**
     * Approves every loan someone else registered against this account
     * that's still waiting on a yes/no — same effect as clicking "Confirmar"
     * on each one individually, just in one step for when there are several.
     */
    public function approveAll(Request $request)
    {
        $pending = Loan::query()
            ->where('person_user_id', $request->user()->id)
            ->where('status', 'pendiente_confirmacion')
            ->get();

        $pending->each(fn (Loan $loan) => $loan->approve());

        return back()->with('status', $pending->isEmpty()
            ? 'No había confirmaciones pendientes.'
            : "Se confirmaron {$pending->count()} préstamo(s)."
        );
    }

    /**
     * Confirms every payment this account's debtors have reported as paid
     * but that's still waiting on this account's yes/no.
     */
    public function confirmAllPayments(Request $request)
    {
        $pending = $request->user()->loans()
            ->where('status', 'pago_pendiente_confirmacion')
            ->where('direction', 'prestado')
            ->get();

        $pending->each(fn (Loan $loan) => $loan->confirmPayment());

        return back()->with('status', $pending->isEmpty()
            ? 'No había pagos pendientes de confirmar.'
            : "Se confirmaron {$pending->count()} pago(s)."
        );
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

    /**
     * Only the debtor can request an amortization, and only while the debt
     * is still active — once it's fully paid there's nothing left to send.
     */
    public function requestAmortization(StoreLoanAmortizationRequest $request, Loan $loan)
    {
        $this->authorizeOwnership($loan);
        abort_unless($loan->direction === 'recibido', 422, 'Solo puedes amortizar una deuda que debes.');
        abort_unless($loan->isPending(), 422, 'Esta deuda no está activa.');

        $loan->requestAmortization((float) $request->validated('amount'));

        $status = $loan->person_user_id
            ? 'Se envió la solicitud de amortización. Se notificó a la otra persona para que la confirme.'
            : 'Pago parcial registrado correctamente.';

        return back()->with('status', $status);
    }

    /**
     * Acted on by the creditor (the counterpart of the debtor's loan the
     * amortization belongs to), not the debtor who requested it.
     */
    public function acceptAmortization(Request $request, LoanAmortization $amortization)
    {
        $creditorLoan = $amortization->loan->counterpart;
        abort_unless($creditorLoan && $creditorLoan->user_id === $request->user()->id, 403);
        abort_unless($amortization->isPending(), 422, 'Esta solicitud ya fue respondida.');

        $creditorLoan->acceptAmortization($amortization);

        return back()->with('status', 'Amortización confirmada.');
    }

    public function rejectAmortization(Request $request, LoanAmortization $amortization)
    {
        $creditorLoan = $amortization->loan->counterpart;
        abort_unless($creditorLoan && $creditorLoan->user_id === $request->user()->id, 403);
        abort_unless($amortization->isPending(), 422, 'Esta solicitud ya fue respondida.');

        $creditorLoan->rejectAmortization($amortization);

        return back()->with('status', 'Amortización rechazada.');
    }

    /**
     * Polled from the index page every ~5s so that when the OTHER side of
     * a loan acts — accepts/rejects an amortization, confirms a payment,
     * sends a new amortization request — this account's own view catches
     * up on its own, without a manual reload.
     */
    public function statuses(Request $request)
    {
        $user = $request->user();
        $ids = array_filter(explode(',', (string) $request->query('ids', '')));

        $loans = Loan::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $ids)
            ->get();

        $creditorCounterpartIds = Loan::query()
            ->where('user_id', $user->id)
            ->where('direction', 'prestado')
            ->pluck('counterpart_loan_id')
            ->filter();

        $amortizationRequestsCount = LoanAmortization::query()
            ->whereIn('loan_id', $creditorCounterpartIds)
            ->where('status', 'pendiente')
            ->count();

        return response()->json([
            'loans' => $loans->mapWithKeys(fn (Loan $loan) => [
                $loan->id => ['status' => $loan->status, 'paid_amount' => (float) $loan->paid_amount],
            ]),
            'amortization_requests_count' => $amortizationRequestsCount,
        ]);
    }

    private function authorizeOwnership(Loan $loan): void
    {
        abort_unless($loan->user_id === auth()->id(), 403);
    }
}
