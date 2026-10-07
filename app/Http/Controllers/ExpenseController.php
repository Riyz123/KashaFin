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
        $user = $request->user();
        $sort = $request->string('sort')->toString();

        $expenses = $user->expenses()
            ->with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('description', 'like', '%'.$request->string('search').'%');
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->when($request->filled('from'), function ($query) use ($request) {
                $query->whereDate('date', '>=', $request->date('from'));
            })
            ->when($request->filled('to'), function ($query) use ($request) {
                $query->whereDate('date', '<=', $request->date('to'));
            })
            ->when($sort === 'amount_desc', fn ($query) => $query->orderByDesc('amount'))
            ->when($sort === 'amount_asc', fn ($query) => $query->orderBy('amount'))
            ->when(! in_array($sort, ['amount_desc', 'amount_asc'], true), fn ($query) => $query->orderByDesc('date'))
            ->paginate(10)
            ->withQueryString();

        $categories = Category::query()->forUser($user)->orderBy('name')->get();

        $totalMonth = $user->expenses()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'totalMonth' => (float) $totalMonth,
            'search' => $request->string('search')->toString(),
            'categoryId' => $request->string('category_id')->toString(),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
            'sort' => $sort,
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
