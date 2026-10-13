<!DOCTYPE html>
<html>
<body style="font-family: -apple-system, Arial, sans-serif; background: #f3f4f6; padding: 24px; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="font-size: 18px; color: #111827;">Hola, {{ $user->name }}</h1>

        <p style="color: #374151; font-size: 14px; line-height: 1.5;">
            Registramos automáticamente tu ingreso fijo programado:
        </p>

        <div style="background: #f3f4f6; border-radius: 6px; padding: 16px; text-align: center; font-size: 20px; font-weight: bold; color: #111827; margin: 16px 0;">
            S/ {{ number_format((float) $income->amount, 2) }}
        </div>

        <p style="color: #374151; font-size: 14px; line-height: 1.5;">
            {{ $income->description ?: 'Sin descripción' }}, con fecha {{ $income->date->format('d/m/Y') }}.
            Si no lo recibiste todavía, puedes eliminarlo desde tu historial de movimientos.
        </p>

        <p style="margin: 24px 0;">
            <a href="{{ route('history.index') }}" style="background: #16a34a; color: #ffffff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px;">
                Ver mi historial
            </a>
        </p>
    </div>
</body>
</html>
