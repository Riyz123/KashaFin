@csrf

<div>
    <x-input-label for="person_name" value="Nombre de la persona" />
    <x-text-input id="person_name" name="person_name" type="text" class="mt-1 block w-full" :value="old('person_name', $loan->person_name ?? '')" required />
    <x-input-error :messages="$errors->get('person_name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="direction" value="Tipo" />
    <select id="direction" name="direction" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
        <option value="prestado" @selected(old('direction', $loan->direction ?? '') === 'prestado')>Yo presté (me deben)</option>
        <option value="recibido" @selected(old('direction', $loan->direction ?? '') === 'recibido')>Me prestaron (yo debo)</option>
    </select>
    <x-input-error :messages="$errors->get('direction')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="amount" value="Monto" />
    <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount', $loan->amount ?? '')" required />
    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="date" value="Fecha" />
    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', optional($loan->date ?? null)->toDateString() ?? now()->toDateString())" required />
    <x-input-error :messages="$errors->get('date')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="due_date" value="Fecha límite (opcional)" />
    <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date', optional($loan->due_date ?? null)->toDateString())" />
    <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="notes" value="Notas (opcional)" />
    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('notes', $loan->notes ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
</div>

<div class="mt-6">
    <x-primary-button>Guardar</x-primary-button>
</div>
