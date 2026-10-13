<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $from = $request->filled('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->filled('to') ? Carbon::parse($request->query('to')) : null;

        $incomes = $user->incomes()->get()->map(fn (Income $income) => [
            'id' => $income->id,
            'type' => 'ingreso',
            'date' => $income->date,
            'description' => $income->description,
            'category' => $income->type === 'fijo' ? 'Ingreso fijo' : 'Ingreso variable',
            'amount' => (float) $income->amount,
        ]);

        $expenses = $user->expenses()->with('category')->get()->map(fn (Expense $expense) => [
            'id' => $expense->id,
            'type' => 'gasto',
            'date' => $expense->date,
            'description' => $expense->description,
            'category' => $expense->category?->name ?? 'Sin categoría',
            'amount' => (float) $expense->amount,
        ]);

        $movements = $incomes->concat($expenses)
            ->when($search, fn ($items) => $items->filter(
                fn ($item) => str_contains(strtolower($item['description'] ?? ''), strtolower($search))
            ))
            ->when($categoryId, fn ($items) => $items->filter(
                fn ($item) => $item['type'] === 'gasto' && Category::find($categoryId)?->name === $item['category']
            ))
            ->when($from, fn ($items) => $items->filter(fn ($item) => $item['date']->gte($from)))
            ->when($to, fn ($items) => $items->filter(fn ($item) => $item['date']->lte($to)))
            ->sortByDesc(fn ($item) => $item['date']->toDateString())
            ->values();

        $categories = Category::query()->forUser($user)->orderBy('name')->get();

        return view('history.index', [
            'movements' => $movements,
            'categories' => $categories,
            'search' => $search,
            'from' => $from,
            'to' => $to,
            'categoryId' => $categoryId,
        ]);
    }

    /**
     * RF41: the complete history, ignoring any filter on screen — unlike
     * the reports export, which is scoped to a date range.
     */
    public function exportCsv(Request $request)
    {
        $user = $request->user();
        $filename = 'historial-completo-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($user) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha', 'Tipo', 'Categoría', 'Descripción', 'Monto']);

            $user->incomes()->orderBy('date')->get()->each(function (Income $income) use ($handle) {
                fputcsv($handle, [
                    $income->date->toDateString(),
                    'Ingreso',
                    $income->type === 'fijo' ? 'Ingreso fijo' : 'Ingreso variable',
                    $income->description,
                    $income->amount,
                ]);
            });

            $user->expenses()->with('category')->orderBy('date')->get()->each(function (Expense $expense) use ($handle) {
                fputcsv($handle, [
                    $expense->date->toDateString(),
                    'Gasto',
                    $expense->category?->name ?? 'Sin categoría',
                    $expense->description,
                    $expense->amount,
                ]);
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * RF42: wipes all income/expense history — deliberately leaves
     * savings_goals and goal_contributions untouched, since those live in
     * their own tables and are never referenced by an expense/income row.
     */
    public function clear(Request $request)
    {
        $user = $request->user();

        $user->incomes()->delete();
        $user->expenses()->delete();

        return redirect()->route('history.index')->with('status', 'Se vació tu historial de ingresos y gastos. Tus metas de ahorro no se vieron afectadas.');
    }

    public function duplicate(Request $request, string $type, int $id)
    {
        abort_unless(in_array($type, ['ingreso', 'gasto'], true), 404);

        $user = $request->user();

        if ($type === 'ingreso') {
            $original = Income::where('user_id', $user->id)->findOrFail($id);
            Income::create([
                'user_id' => $user->id,
                'amount' => $original->amount,
                'date' => now()->toDateString(),
                'description' => $original->description,
                'type' => $original->type,
                'frequency' => null,
                'next_occurrence_date' => null,
            ]);
        } else {
            $original = Expense::where('user_id', $user->id)->findOrFail($id);
            Expense::create([
                'user_id' => $user->id,
                'category_id' => $original->category_id,
                'amount' => $original->amount,
                'date' => now()->toDateString(),
                'description' => $original->description,
            ]);
        }

        return redirect()->route('history.index')->with('status', 'Movimiento duplicado con la fecha de hoy.');
    }
}
