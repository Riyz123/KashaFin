<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Category;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = $request->user()->expenses()
            ->with('category')
            ->orderByDesc('date')
            ->paginate(10);

        $totalMonth = $request->user()->expenses()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        return view('expenses.index', [
            'expenses' => $expenses,
            'totalMonth' => (float) $totalMonth,
        ]);
    }

    public function create(Request $request)
    {
        $categories = Category::query()->forUser($request->user())->orderBy('name')->get();

        return view('expenses.create', ['categories' => $categories]);
    }

    public function store(StoreExpenseRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        Expense::create($data);

        return redirect()->route('expenses.index')->with('status', 'Gasto registrado correctamente.');
    }

    public function edit(Request $request, Expense $expense)
    {
        $this->authorizeOwnership($expense);

        $categories = Category::query()->forUser($request->user())->orderBy('name')->get();

        return view('expenses.edit', ['expense' => $expense, 'categories' => $categories]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        $this->authorizeOwnership($expense);

        $expense->update($request->validated());

        return redirect()->route('expenses.index')->with('status', 'Gasto actualizado correctamente.');
    }

    public function destroy(Expense $expense)
    {
        $this->authorizeOwnership($expense);

        $expense->delete();

        return redirect()->route('expenses.index')->with('status', 'Gasto eliminado correctamente.');
    }

    private function authorizeOwnership(Expense $expense): void
    {
        abort_unless($expense->user_id === auth()->id(), 403);
    }
}
