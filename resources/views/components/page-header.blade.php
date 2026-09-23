@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-start justify-between gap-3']) }}>
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex items-center gap-2">{{ $slot }}</div>
    @endif
</div>
