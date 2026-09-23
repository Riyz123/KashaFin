<x-app-layout title="Nuevo presupuesto">
    <x-page-header title="Nuevo presupuesto" />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('budgets.store') }}">
            @csrf
            @include('budgets._form', ['categories' => $categories])
        </form>
    </x-card>
</x-app-layout>
