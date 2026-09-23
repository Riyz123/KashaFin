@props(['percent' => 0, 'warningAt' => 90])

@php
$clamped = max(0, min(100, $percent));
$colorClass = $percent >= $warningAt ? 'bg-red-500' : ($percent >= 60 ? 'bg-amber-500' : 'bg-brand-500');
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
        <div class="h-full rounded-full {{ $colorClass }} transition-all" style="width: {{ $clamped }}%"></div>
    </div>
</div>
