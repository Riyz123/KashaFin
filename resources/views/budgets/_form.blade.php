@props(['budget' => null, 'categories'])

<div class="space-y-5">
    <div>
        <x-input-label for="category_id" value="Categoría" />
        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500" required>
            <option value="">Selecciona una categoría</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $budget->category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="period_month" value="Mes" />
        <x-text-input id="period_month" name="period_month" type="month" class="mt-1 block w-full" :value="old('period_month', optional($budget?->period_month)->format('Y-m') ?? now()->format('Y-m'))" required />
        <x-input-error :messages="$errors->get('period_month')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="amount" value="Monto del presupuesto" />
        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount', $budget->amount ?? '')" required />
        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>{{ $budget ? 'Guardar cambios' : 'Crear presupuesto' }}</x-primary-button>
        <a href="{{ route('budgets.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">Cancelar</a>
    </div>
</div>
