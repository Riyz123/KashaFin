@props(['user'])

<span class="inline-flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400">
    <span class="h-1.5 w-1.5 rounded-full {{ $user->isOnline() ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
    @if ($user->isOnline())
        En línea
    @elseif ($user->last_seen_at)
        Última vez {{ $user->last_seen_at->diffForHumans() }}
    @else
        Nunca ha entrado
    @endif
</span>
