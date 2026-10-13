<!DOCTYPE html>
<html>
<body style="font-family: -apple-system, Arial, sans-serif; background: #f3f4f6; padding: 24px; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="font-size: 18px; color: #111827;">Hola, {{ $user->name }}</h1>

        <p style="color: #374151; font-size: 14px; line-height: 1.5;">
            Llevas {{ $daysInactive }} días sin registrar un gasto en KashaFin. Si gastaste algo en estos días,
            anótalo para que tu proyección de liquidez siga siendo precisa.
        </p>

        <p style="margin: 24px 0;">
            <a href="{{ route('expenses.index') }}" style="background: #16a34a; color: #ffffff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px;">
                Registrar un gasto
            </a>
        </p>

        <p style="color: #9ca3af; font-size: 12px;">
            Puedes desactivar este recordatorio desde Configuración en cualquier momento.
        </p>
    </div>
</body>
</html>
