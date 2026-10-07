<!DOCTYPE html>
<html>
<body style="font-family: -apple-system, Arial, sans-serif; background: #f3f4f6; padding: 24px; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="font-size: 18px; color: #111827;">¡Hola, {{ $user->name }}!</h1>

        <p style="color: #374151; font-size: 14px; line-height: 1.5;">
            Tu cuenta de KashaFin ya está aprobada y lista para usarse.
        </p>

        <p style="color: #374151; font-size: 14px; line-height: 1.5;">
            Esta es tu contraseña temporal para tu primer ingreso:
        </p>

        <div style="background: #f3f4f6; border-radius: 6px; padding: 16px; text-align: center; font-size: 20px; font-weight: bold; letter-spacing: 1px; color: #111827; margin: 16px 0;">
            {{ $temporaryPassword }}
        </div>

        <p style="color: #374151; font-size: 14px; line-height: 1.5;">
            Inicia sesión con tu correo (<strong>{{ $user->email }}</strong>) y esta contraseña. Te pediremos que la
            cambies por una propia antes de poder usar el resto de la app.
        </p>

        <p style="margin: 24px 0;">
            <a href="{{ route('login') }}" style="background: #4f46e5; color: #ffffff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px;">
                Iniciar sesión
            </a>
        </p>

        <p style="color: #9ca3af; font-size: 12px;">
            Si no esperabas este correo, puedes ignorarlo.
        </p>
    </div>
</body>
</html>
