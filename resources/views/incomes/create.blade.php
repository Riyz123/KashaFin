<x-app-layout title="Nuevo ingreso">
    <x-page-header title="Registrar ingreso" subtitle="Agrega un ingreso fijo o variable." />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('incomes.store') }}">
            @csrf
            @include('incomes._form')
        </form>
    </x-card>
</x-app-layout>
