<x-app-layout title="Editar meta">
    <x-page-header title="Editar meta" />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('goals.update', $goal) }}">
            @csrf
            @method('PUT')
            @include('goals._form', ['goal' => $goal])
        </form>
    </x-card>
</x-app-layout>
