<x-app-layout title="Editar presupuesto">
    <x-page-header title="Editar presupuesto" />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('budgets.update', $budget) }}">
            @csrf
            @method('PUT')
            @include('budgets._form', ['budget' => $budget, 'categories' => $categories])
        </form>
    </x-card>
</x-app-layout>
