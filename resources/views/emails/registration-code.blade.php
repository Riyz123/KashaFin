<!DOCTYPE html>
<html>
<body style="font-family: -apple-system, Arial, sans-serif; background: #f3f4f6; padding: 24px; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="font-size: 18px; color: #111827;">¡Hola, {{ $name }}!</h1>

        <p style="color: #374151; font-size: 14px; line-height: 1.5;">
            Usa este código para confirmar tu correo y continuar tu solicitud de cuenta en KashaFin:
        </p>

        <div style="background: #f3f4f6; border-radius: 6px; padding: 16px; text-align: center; font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #111827; margin: 16px 0;">
            {{ $code }}
        </div>

        <p style="color: #374151; font-size: 14px; line-height: 1.5;">
            El código vence en 15 minutos. Si no fuiste tú quien pidió registrarse en KashaFin, puedes ignorar este
            correo.
        </p>
    </div>
</body>
</html>
