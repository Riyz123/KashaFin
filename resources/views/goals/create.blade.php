<x-app-layout title="Nueva meta">
    <x-page-header title="Nueva meta de ahorro" />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('goals.store') }}">
            @csrf
            @include('goals._form')
        </form>
    </x-card>
</x-app-layout>
