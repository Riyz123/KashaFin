@csrf

<div
    x-data="{
        label: @js(old('person_name', $loan->person_name ?? '')),
        personUserId: @js(old('person_user_id', $loan->person_user_id ?? null)),
        results: [], open: false, searching: false,
        async runSearch() {
            this.personUserId = null; // typing again invalidates a previous pick
            if (this.label.length < 2) { this.results = []; return; }
            this.searching = true;
            const res = await fetch(`{{ route('shared-expenses.search-users') }}?q=${encodeURIComponent(this.label)}`);
            this.results = await res.json();
            this.searching = false;
        },
        pick(user) {
            this.personUserId = user.id;
            this.label = user.name;
            this.results = [];
            this.open = false;
        },
    }"
>
    <x-input-label for="person_name" value="Nombre de la persona o entidad" />
    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
        Busca una cuenta de KashaFin si la persona es de la institución, o simplemente escribe el nombre si es
        externa (una tienda, un familiar, etc.) — no hace falta que tenga cuenta.
    </p>
    <div class="relative mt-1">
        <input
            id="person_name" name="person_name" type="text" required
            x-model="label"
            @input="runSearch()"
            @focus="open = true"
            class="block w-full rounded-md text-sm focus:ring-brand-500 dark:bg-gray-900 dark:text-gray-300"
            :class="personUserId ? 'border-brand-400 focus:border-brand-400' : 'border-gray-300 focus:border-brand-500 dark:border-gray-700'"
            autocomplete="off"
        >
        <input type="hidden" name="person_user_id" :value="personUserId">

        <div x-show="open && results.length > 0" x-cloak @click.outside="open = false" class="absolute z-10 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <template x-for="user in results" :key="user.id">
                <button type="button" @click="pick(user)" class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    <span x-text="user.name"></span>
                    <span class="text-gray-400" x-text="`(${user.email})`"></span>
                </button>
            </template>
        </div>
    </div>
    <p x-show="personUserId" x-cloak class="mt-1 text-xs text-brand-600">✓ Vinculado a una cuenta de KashaFin</p>
    <x-input-error :messages="$errors->get('person_name')" class="mt-2" />
    <x-input-error :messages="$errors->get('person_user_id')" class="mt-2" />
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
