<x-app-layout title="Nuevo préstamo">
    <x-page-header title="Nuevo préstamo" />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('loans.store') }}">
            @include('loans._form')
        </form>
    </x-card>
</x-app-layout>
