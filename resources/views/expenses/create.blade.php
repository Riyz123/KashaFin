<x-app-layout title="Nuevo gasto">
    <x-page-header title="Registrar gasto" subtitle="Registra un gasto en menos de 3 pasos." />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('expenses.store') }}">
            @csrf
            @include('expenses._form', ['categories' => $categories])
        </form>
    </x-card>
</x-app-layout>
