@props(['label', 'value', 'tone' => 'default', 'icon' => null])

@php
$toneClasses = [
    'default' => 'text-gray-900 dark:text-gray-100',
    'positive' => 'text-brand-700 dark:text-brand-400',
    'negative' => 'text-red-600 dark:text-red-400',
][$tone] ?? 'text-gray-900 dark:text-gray-100';
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-5']) }}>
    <div class="flex items-center justify-between">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
        @if ($icon)
            <x-icon :name="$icon" class="w-5 h-5 text-brand-500" />
        @endif
    </div>
    <p class="mt-2 text-2xl font-bold {{ $toneClasses }}">{{ $value }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $slot }}</div>
    @endif
</div>
