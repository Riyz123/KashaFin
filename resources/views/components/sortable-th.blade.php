@props(['field', 'label', 'align' => 'left'])
@php
    $currentSort = request()->query('sort');
    $currentDir = request()->query('dir') === 'desc' ? 'desc' : 'asc';
    $isActive = $currentSort === $field;
    $nextDir = $isActive && $currentDir === 'asc' ? 'desc' : 'asc';

    $query = collect(request()->query())
        ->except('page')
        ->merge(['sort' => $field, 'dir' => $nextDir])
        ->all();
@endphp
<th class="py-2 pr-4 {{ $align === 'right' ? 'text-right' : 'text-left' }}">
    <a href="{{ request()->url() }}?{{ http_build_query($query) }}" class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
        {{ $label }}
        @if ($isActive)
            <span class="text-brand-600">{{ $currentDir === 'asc' ? '▲' : '▼' }}</span>
        @else
            <span class="text-gray-300 dark:text-gray-600">↕</span>
        @endif
    </a>
</th>
