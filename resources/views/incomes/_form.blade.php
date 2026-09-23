@props(['income' => null])

<div x-data="{ type: '{{ old('type', $income->type ?? 'variable') }}' }" class="space-y-5">
    <div>
        <x-input-label for="amount" value="Monto" />
        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount', $income->amount ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="date" value="Fecha" />
        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', optional($income?->date)->toDateString() ?? now()->toDateString())" required />
        <x-input-error :messages="$errors->get('date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" value="Descripción" />
        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $income->description ?? '')" required />
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label value="Tipo" />
        <div class="mt-2 flex gap-4">
            <label class="flex items-center gap-2 text-sm">
                <input type="radio" name="type" value="variable" x-model="type" class="text-brand-600 focus:ring-brand-500">
                Variable
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="radio" name="type" value="fijo" x-model="type" class="text-brand-600 focus:ring-brand-500">
                Fijo
            </label>
        </div>
        <x-input-error :messages="$errors->get('type')" class="mt-2" />
    </div>

    <div x-show="type === 'fijo'" x-cloak>
        <x-input-label for="frequency" value="Frecuencia" />
        <select id="frequency" name="frequency" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
            <option value="">Selecciona una frecuencia</option>
            <option value="semanal" @selected(old('frequency', $income->frequency ?? '') === 'semanal')>Semanal</option>
            <option value="quincenal" @selected(old('frequency', $income->frequency ?? '') === 'quincenal')>Quincenal</option>
            <option value="mensual" @selected(old('frequency', $income->frequency ?? '') === 'mensual')>Mensual</option>
        </select>
        <x-input-error :messages="$errors->get('frequency')" class="mt-2" />
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>{{ $income ? 'Guardar cambios' : 'Registrar ingreso' }}</x-primary-button>
        <a href="{{ route('incomes.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">Cancelar</a>
    </div>
</div>
