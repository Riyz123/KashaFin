@props(['amount'])

@php
$symbol = auth()->user()?->settings?->currency_symbol ?? 'S/';
@endphp

<span {{ $attributes }}>{{ $symbol }} {{ number_format((float) $amount, 2) }}</span>
