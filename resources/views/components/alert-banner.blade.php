@props(['tone' => 'warning'])

@php
$toneClasses = [
    'warning' => 'bg-amber-50 border-amber-300 text-amber-800 dark:bg-amber-900/30 dark:border-amber-700 dark:text-amber-200',
    'danger' => 'bg-red-50 border-red-300 text-red-800 dark:bg-red-900/30 dark:border-red-700 dark:text-red-200',
][$tone] ?? 'bg-amber-50 border-amber-300 text-amber-800';
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {$toneClasses}"]) }}>
    <x-icon name="exclamation" class="w-5 h-5 shrink-0 mt-0.5" />
    <div>{{ $slot }}</div>
</div>
