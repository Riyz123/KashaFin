<!DOCTYPE html>
<html>
<body style="font-family: -apple-system, Arial, sans-serif; background: #f3f4f6; padding: 24px; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="font-size: 18px; color: #111827;">Alerta de liquidez</h1>

        <p style="color: #374151; font-size: 14px; line-height: 1.5;">
            Hola {{ $user->name }}, tu proyección de saldo para los próximos 7 días cayó por debajo de tu umbral
            configurado.
        </p>

        <table style="width: 100%; margin: 16px 0; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #374151; font-size: 14px;">Saldo proyectado más bajo</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #dc2626;">S/ {{ number_format($projectedBalance, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #374151; font-size: 14px;">Umbral configurado</td>
                <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #111827;">S/ {{ number_format($threshold, 2) }}</td>
            </tr>
        </table>

        <p style="color: #374151; font-size: 14px; line-height: 1.5;">
            Te recomendamos revisar tus próximos gastos e ingresos en KashaFin antes de que esto se convierta en un
            problema.
        </p>

        <p style="margin: 24px 0;">
            <a href="{{ route('dashboard') }}" style="background: #16a34a; color: #ffffff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px;">
                Ver mi panel
            </a>
        </p>
    </div>
</body>
</html>
