<x-app-layout title="Editar gasto">
    <x-page-header title="Editar gasto" />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('expenses.update', $expense) }}">
            @csrf
            @method('PUT')
            @include('expenses._form', ['expense' => $expense, 'categories' => $categories])
        </form>
    </x-card>
</x-app-layout>
