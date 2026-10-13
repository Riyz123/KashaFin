<x-app-layout title="Editar préstamo">
    <x-page-header title="Editar préstamo" />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('loans.update', $loan) }}">
            @method('PUT')
            @include('loans._form')
        </form>
    </x-card>
</x-app-layout>
