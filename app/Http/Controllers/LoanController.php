<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanRequest;
use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $loans = $request->user()->loans()->latest('date')->get();

        $totals = [
            'owed_to_me' => (float) $loans->where('direction', 'prestado')->where('status', 'pendiente')->sum('amount'),
            'i_owe' => (float) $loans->where('direction', 'recibido')->where('status', 'pendiente')->sum('amount'),
        ];

        return view('loans.index', ['loans' => $loans, 'totals' => $totals]);
    }

    public function create()
    {
        return view('loans.create');
    }

    public function store(StoreLoanRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        Loan::create($data);

        return redirect()->route('loans.index')->with('status', 'Préstamo registrado correctamente.');
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

        $loan->markPaid();

        return redirect()->route('loans.index')->with('status', 'Préstamo marcado como pagado.');
    }

    private function authorizeOwnership(Loan $loan): void
    {
        abort_unless($loan->user_id === auth()->id(), 403);
    }
}
