<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; font-size: 12px; }
        h1 { color: #14532D; font-size: 20px; margin-bottom: 0; }
        .subtitle { color: #6b7280; margin-top: 4px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        th { background-color: #DCFCE7; color: #14532D; text-transform: uppercase; font-size: 10px; }
        .summary { margin-top: 20px; }
        .summary td { border: none; padding: 4px 8px; }
        .positive { color: #15803D; font-weight: bold; }
        .negative { color: #DC2626; font-weight: bold; }
    </style>
</head>
<body>
    <h1>KashaFin — Reporte financiero</h1>
    <p class="subtitle">{{ $user->name }} &middot; {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</p>

    <table class="summary">
        <tr>
            <td><strong>Ingresos:</strong></td>
            <td>S/ {{ number_format($summary['income'], 2) }}</td>
            <td><strong>Gastos:</strong></td>
            <td>S/ {{ number_format($summary['expense'], 2) }}</td>
            <td><strong>Balance:</strong></td>
            <td class="{{ $summary['balance'] >= 0 ? 'positive' : 'negative' }}">S/ {{ number_format($summary['balance'], 2) }}</td>
        </tr>
    </table>

    <h3>Gastos por categoría</h3>
    <table>
        <thead>
            <tr><th>Categoría</th><th>Total</th></tr>
        </thead>
        <tbody>
            @forelse ($byCategory as $row)
                <tr>
                    <td>{{ $row['category'] }}</td>
                    <td>S/ {{ number_format($row['total'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No hay gastos en este periodo.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
