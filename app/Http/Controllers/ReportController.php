<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports)
    {
        [$from, $to] = $this->resolveRange($request);
        $user = $request->user();

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'byCategory' => $reports->expensesByCategory($user, $from, $to),
            'summary' => $reports->incomeVsExpense($user, $from, $to),
        ]);
    }

    public function exportPdf(Request $request, ReportService $reports)
    {
        [$from, $to] = $this->resolveRange($request);
        $user = $request->user();

        $pdf = Pdf::loadView('pdf.report', [
            'user' => $user,
            'from' => $from,
            'to' => $to,
            'byCategory' => $reports->expensesByCategory($user, $from, $to),
            'summary' => $reports->incomeVsExpense($user, $from, $to),
        ]);

        return $pdf->download('reporte-kashafin-'.$from->format('Y-m-d').'-a-'.$to->format('Y-m-d').'.pdf');
    }

    public function exportCsv(Request $request, ReportService $reports)
    {
        [$from, $to] = $this->resolveRange($request);
        $user = $request->user();

        $filename = 'reporte-kashafin-'.$from->format('Y-m-d').'-a-'.$to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($user, $from, $to) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha', 'Tipo', 'Categoría', 'Descripción', 'Monto']);

            $user->incomes()->between($from, $to)->orderBy('date')->get()->each(function ($income) use ($handle) {
                fputcsv($handle, [$income->date->toDateString(), 'Ingreso', '-', $income->description, $income->amount]);
            });

            $user->expenses()->with('category')->between($from, $to)->orderBy('date')->get()->each(function ($expense) use ($handle) {
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

    private function resolveRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))
            : now()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))
            : now()->endOfMonth();

        return [$from->startOfDay(), $to->endOfDay()];
    }
}
