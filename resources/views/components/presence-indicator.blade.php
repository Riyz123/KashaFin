@props(['user'])

{{--
    The server-rendered value is the fallback shown on first paint (and if
    JS is disabled, or the poll hasn't resolved yet) — the parent element's
    x-data="presencePoll(...)" owns `state`, keyed by user id, which Alpine
    lets this child read directly without needing its own x-data.
--}}
<span
    x-data="{
        id: {{ $user->id }},
        fallback: { online: {{ $user->isOnline() ? 'true' : 'false' }}, label: @js($user->presenceLabel()) },
    }"
    class="inline-flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400"
>
    <span class="h-1.5 w-1.5 rounded-full {{ $user->isOnline() ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}" :class="(state[id] ?? fallback).online ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"></span>
    <span x-text="(state[id] ?? fallback).label">{{ $user->presenceLabel() }}</span>
</span>
