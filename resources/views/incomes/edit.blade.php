<x-app-layout title="Editar ingreso">
    <x-page-header title="Editar ingreso" />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('incomes.update', $income) }}">
            @csrf
            @method('PUT')
            @include('incomes._form', ['income' => $income])
        </form>
    </x-card>
</x-app-layout>
