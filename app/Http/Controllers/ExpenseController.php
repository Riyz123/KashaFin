<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Category;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $sort = $request->string('sort')->toString();
        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';

        $expenses = $user->expenses()
            ->with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('description', 'like', '%'.$request->string('search').'%');
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->string('type'));
            })
            ->when($request->filled('from'), function ($query) use ($request) {
                $query->whereDate('date', '>=', $request->date('from'));
            })
            ->when($request->filled('to'), function ($query) use ($request) {
                $query->whereDate('date', '<=', $request->date('to'));
            })
            ->when($sort === 'category', function ($query) use ($dir) {
                $query->leftJoin('categories', 'categories.id', '=', 'expenses.category_id')
                    ->orderBy('categories.name', $dir)
                    ->select('expenses.*');
            })
            ->when($sort === 'amount', fn ($query) => $query->orderBy('amount', $dir))
            ->when($sort === 'date', fn ($query) => $query->orderBy('date', $dir))
            ->when(! in_array($sort, ['category', 'amount', 'date'], true), fn ($query) => $query->orderByDesc('date'))
            ->paginate(10)
            ->withQueryString();

        $categories = Category::query()->forUser($user)->orderBy('name')->get();

        $totalMonth = $user->expenses()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        $fixedMonth = $user->expenses()
            ->fixed()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        $variableMonth = $user->expenses()
            ->variable()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'totalMonth' => (float) $totalMonth,
            'fixedMonth' => (float) $fixedMonth,
            'variableMonth' => (float) $variableMonth,
            'search' => $request->string('search')->toString(),
            'categoryId' => $request->string('category_id')->toString(),
            'type' => $request->string('type')->toString(),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
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

        if ($data['type'] === 'fijo') {
            $date = Carbon::parse($data['date']);
            $expense = new Expense($data);
            $data['next_occurrence_date'] = $expense->nextOccurrenceAfter($date)->toDateString();
        } else {
            $data['frequency'] = null;
            $data['next_occurrence_date'] = null;
        }

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

        $data = $request->validated();

        if ($data['type'] === 'fijo') {
            $date = Carbon::parse($data['date']);
            $temp = new Expense($data);
            $data['next_occurrence_date'] = $temp->nextOccurrenceAfter($date)->toDateString();
        } else {
            $data['frequency'] = null;
            $data['next_occurrence_date'] = null;
        }

        $expense->update($data);

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
