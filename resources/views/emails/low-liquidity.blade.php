<x-mail::message>
# Alerta de liquidez

Hola {{ $user->name }},

Tu proyección de saldo para los próximos 7 días cayó por debajo de tu umbral configurado.

- **Saldo proyectado más bajo:** S/ {{ number_format($projectedBalance, 2) }}
- **Umbral configurado:** S/ {{ number_format($threshold, 2) }}

Te recomendamos revisar tus próximos gastos e ingresos en KashaFin antes de que esto se convierta en un problema.

<x-mail::button :url="route('dashboard')">
Ver mi panel
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
