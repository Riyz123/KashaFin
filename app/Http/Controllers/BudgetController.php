<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetRequest;
use App\Models\Budget;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $periodMonth = Carbon::now()->startOfMonth();

        $budgets = $request->user()->budgets()
            ->with('category')
            ->whereDate('period_month', $periodMonth->toDateString())
            ->get();

        return view('budgets.index', [
            'budgets' => $budgets,
            'periodMonth' => $periodMonth,
        ]);
    }

    public function create(Request $request)
    {
        $categories = Category::query()->forUser($request->user())->orderBy('name')->get();

        return view('budgets.create', ['categories' => $categories]);
    }

    public function store(StoreBudgetRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $periodMonth = Carbon::parse($data['period_month'])->startOfMonth();

        $budget = Budget::query()
            ->where('user_id', $data['user_id'])
            ->where('category_id', $data['category_id'])
            ->whereDate('period_month', $periodMonth->toDateString())
            ->first();

        if ($budget) {
            $budget->update(['amount' => $data['amount']]);
        } else {
            Budget::create([
                'user_id' => $data['user_id'],
                'category_id' => $data['category_id'],
                'period_month' => $periodMonth,
                'amount' => $data['amount'],
            ]);
        }

        return redirect()->route('budgets.index')->with('status', 'Presupuesto guardado correctamente.');
    }

    public function edit(Request $request, Budget $budget)
    {
        $this->authorizeOwnership($budget);

        $categories = Category::query()->forUser($request->user())->orderBy('name')->get();

        return view('budgets.edit', ['budget' => $budget, 'categories' => $categories]);
    }

    public function update(StoreBudgetRequest $request, Budget $budget)
    {
        $this->authorizeOwnership($budget);

        $data = $request->validated();
        $data['period_month'] = Carbon::parse($data['period_month'])->startOfMonth()->toDateString();

        $budget->update($data);

        return redirect()->route('budgets.index')->with('status', 'Presupuesto actualizado correctamente.');
    }

    public function destroy(Budget $budget)
    {
        $this->authorizeOwnership($budget);

        $budget->delete();

        return redirect()->route('budgets.index')->with('status', 'Presupuesto eliminado correctamente.');
    }

    private function authorizeOwnership(Budget $budget): void
    {
        abort_unless($budget->user_id === auth()->id(), 403);
    }
}
