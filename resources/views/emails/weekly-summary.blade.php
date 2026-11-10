<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Tu resumen semanal de KashaFin</title>
</head>
<body style="font-family: -apple-system, Arial, sans-serif; background: #f3f4f6; padding: 24px; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="font-size: 18px; color: #111827;">Tu semana en KashaFin</h1>
        <p style="color: #6b7280; font-size: 12px;">{{ $periodFrom->format('d/m/Y') }} al {{ $periodTo->format('d/m/Y') }}</p>

        <table style="width: 100%; margin: 16px 0; border-collapse: collapse;">
            <tr>
                <th scope="row" style="padding: 8px 0; color: #374151; font-size: 14px; font-weight: normal; text-align: left;">Ingresos</th>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #16a34a;">S/ {{ number_format($summary['income'], 2) }}</td>
            </tr>
            <tr>
                <th scope="row" style="padding: 8px 0; color: #374151; font-size: 14px; font-weight: normal; text-align: left;">Gastos</th>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #dc2626;">S/ {{ number_format($summary['expense'], 2) }}</td>
            </tr>
            <tr>
                <th scope="row" style="padding: 8px 0; color: #111827; font-size: 14px; font-weight: bold; border-top: 1px solid #e5e7eb; text-align: left;">Balance de la semana</th>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; border-top: 1px solid #e5e7eb; color: {{ $summary['balance'] >= 0 ? '#16a34a' : '#dc2626' }};">S/ {{ number_format($summary['balance'], 2) }}</td>
            </tr>
        </table>

        @if ($byCategory->isNotEmpty())
            <p style="color: #374151; font-size: 14px; font-weight: bold; margin-bottom: 8px;">Gastos por categoría</p>
            <ul style="padding-left: 18px; margin: 0; color: #374151; font-size: 13px;">
                @foreach ($byCategory as $row)
                    <li>{{ $row['category'] }}: S/ {{ number_format($row['total'], 2) }}</li>
                @endforeach
            </ul>
        @endif

        <p style="margin: 24px 0;">
            <a href="{{ route('reports.index') }}" style="background: #16a34a; color: #ffffff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px;">
                Ver reportes completos
            </a>
        </p>

        <p style="color: #9ca3af; font-size: 12px;">
            Puedes desactivar este resumen semanal desde Configuración en cualquier momento.
        </p>
    </div>
</body>
</html>
