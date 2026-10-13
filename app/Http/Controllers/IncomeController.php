<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeRequest;
use App\Http\Requests\UpdateIncomeRequest;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $sort = in_array($request->query('sort'), ['date', 'type', 'amount'], true) ? $request->query('sort') : 'date';
        $dir = $request->filled('sort') && $request->query('dir') !== 'desc' ? 'asc' : 'desc';

        $incomes = $request->user()->incomes()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('description', 'like', '%'.$request->string('search').'%');
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
            ->orderBy($sort, $dir)
            ->paginate(10)
            ->withQueryString();

        $totalMonth = $request->user()->incomes()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        $fixedMonth = $request->user()->incomes()
            ->fixed()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        $variableMonth = $request->user()->incomes()
            ->variable()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        return view('incomes.index', [
            'incomes' => $incomes,
            'totalMonth' => (float) $totalMonth,
            'fixedMonth' => (float) $fixedMonth,
            'variableMonth' => (float) $variableMonth,
            'search' => $request->string('search')->toString(),
            'type' => $request->string('type')->toString(),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
        ]);
    }

    public function create()
    {
        return view('incomes.create');
    }

    public function store(StoreIncomeRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        if ($data['type'] === 'fijo') {
            $date = Carbon::parse($data['date']);
            $income = new Income($data);
            $data['next_occurrence_date'] = $income->nextOccurrenceAfter($date)->toDateString();
        } else {
            $data['frequency'] = null;
            $data['next_occurrence_date'] = null;
        }

        Income::create($data);

        return redirect()->route('incomes.index')->with('status', 'Ingreso registrado correctamente.');
    }

    public function edit(Income $income)
    {
        $this->authorizeOwnership($income);

        return view('incomes.edit', ['income' => $income]);
    }

    public function update(UpdateIncomeRequest $request, Income $income)
    {
        $this->authorizeOwnership($income);

        $data = $request->validated();

        if ($data['type'] === 'fijo') {
            $date = Carbon::parse($data['date']);
            $temp = new Income($data);
            $data['next_occurrence_date'] = $temp->nextOccurrenceAfter($date)->toDateString();
        } else {
            $data['frequency'] = null;
            $data['next_occurrence_date'] = null;
        }

        $income->update($data);

        return redirect()->route('incomes.index')->with('status', 'Ingreso actualizado correctamente.');
    }

    public function destroy(Income $income)
    {
        $this->authorizeOwnership($income);

        $income->delete();

        return redirect()->route('incomes.index')->with('status', 'Ingreso eliminado correctamente.');
    }

    private function authorizeOwnership(Income $income): void
    {
        abort_unless($income->user_id === auth()->id(), 403);
    }
}
