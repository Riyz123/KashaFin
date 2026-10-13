<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSharedExpenseRequest;
use App\Models\Category;
use App\Models\Expense;
use App\Models\SharedExpense;
use App\Models\SharedExpenseParticipant;
use Illuminate\Http\Request;

class SharedExpenseController extends Controller
{
    public function index(Request $request)
    {
        $sharedExpenses = $request->user()->sharedExpenses()
            ->with('participants', 'category')
            ->latest('date')
            ->get();

        $totalOwedToMe = $sharedExpenses->sum(fn (SharedExpense $shared) => $shared->totalOwedPending());

        return view('shared-expenses.index', ['sharedExpenses' => $sharedExpenses, 'totalOwedToMe' => $totalOwedToMe]);
    }

    public function create(Request $request)
    {
        $categories = Category::query()->forUser($request->user())->orderBy('name')->get();

        return view('shared-expenses.create', ['categories' => $categories]);
    }

    /**
     * Only `my_share` becomes a real Expense (the student's own spending);
     * the rest is tracked as money owed back by each participant, never
     * counted twice.
     */
    public function store(StoreSharedExpenseRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();
        $myShare = (float) ($data['my_share'] ?? 0);

        $expense = null;

        if ($myShare > 0) {
            $expense = Expense::create([
                'user_id' => $user->id,
                'category_id' => $data['category_id'] ?? null,
                'amount' => $myShare,
                'date' => $data['date'],
                'description' => $data['description'].' (mi parte)',
            ]);
        }

        $sharedExpense = SharedExpense::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'] ?? null,
            'expense_id' => $expense?->id,
            'description' => $data['description'],
            'total_amount' => $data['total_amount'],
            'my_share' => $myShare,
            'date' => $data['date'],
        ]);

        foreach ($data['participants'] as $participant) {
            $sharedExpense->participants()->create([
                'name' => $participant['name'],
                'amount_owed' => $participant['amount_owed'],
            ]);
        }

        return redirect()->route('shared-expenses.index')->with('status', 'Gasto compartido registrado correctamente.');
    }

    public function destroy(SharedExpense $sharedExpense)
    {
        $this->authorizeOwnership($sharedExpense);

        $sharedExpense->expense?->delete();
        $sharedExpense->delete();

        return redirect()->route('shared-expenses.index')->with('status', 'Gasto compartido eliminado.');
    }

    public function togglePaid(SharedExpenseParticipant $participant)
    {
        $this->authorizeOwnership($participant->sharedExpense);

        $participant->update(['is_paid' => ! $participant->is_paid]);

        return back()->with('status', $participant->is_paid ? 'Marcado como pagado.' : 'Marcado como pendiente.');
    }

    private function authorizeOwnership(SharedExpense $sharedExpense): void
    {
        abort_unless($sharedExpense->user_id === auth()->id(), 403);
    }
}
