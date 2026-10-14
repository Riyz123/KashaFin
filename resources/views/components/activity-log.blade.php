@props(['logName', 'limit' => 5])

@php($activities = \App\Support\ActivityLog::forCurrentUser($logName)->limit($limit)->get())

@if ($activities->isNotEmpty())
    <x-card class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Trazabilidad reciente</h3>
            <a href="{{ route('activity.index', ['section' => $logName]) }}" class="text-xs text-brand-600 hover:underline">Ver todo</a>
        </div>
        <div class="space-y-2">
            @foreach ($activities as $activity)
                <div class="flex items-start justify-between gap-3 text-xs">
                    <span class="text-gray-600 dark:text-gray-300">{{ $activity->description }}</span>
                    <span class="shrink-0 text-gray-400">{{ $activity->created_at->diffForHumans() }}</span>
                </div>
            @endforeach
        </div>
    </x-card>
@endif
