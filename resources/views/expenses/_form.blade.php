@props(['expense' => null, 'categories'])

<div x-data="{ showNewCategory: false }" class="space-y-5">
    <div>
        <x-input-label for="amount" value="Monto" />
        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount', $expense->amount ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="date" value="Fecha" />
        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', optional($expense?->date)->toDateString() ?? now()->toDateString())" required />
        <x-input-error :messages="$errors->get('date')" class="mt-2" />
    </div>

    <div>
        <div class="flex items-center justify-between">
            <x-input-label for="category_id" value="Categoría" />
            <button type="button" @click="showNewCategory = !showNewCategory" class="text-xs text-brand-600 hover:underline">+ nueva categoría</button>
        </div>
        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
            <option value="">Sin categoría</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $expense->category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />

        <div x-show="showNewCategory" x-cloak class="mt-3 flex gap-2">
            <input type="text" id="new-category-name" placeholder="Nombre de la categoría" class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
            <button
                type="button"
                class="rounded-md bg-brand-600 px-3 py-1 text-xs font-semibold text-white hover:bg-brand-700"
                @click="
                    const name = document.getElementById('new-category-name').value.trim();
                    if (!name) return;
                    fetch('{{ route('categories.store') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ name }),
                    }).then(r => r.json()).then(cat => {
                        const select = document.getElementById('category_id');
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.text = cat.name;
                        option.selected = true;
                        select.appendChild(option);
                        showNewCategory = false;
                    });
                "
            >Agregar</button>
        </div>
    </div>

    <div>
        <x-input-label for="description" value="Descripción (opcional)" />
        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $expense->description ?? '')" />
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>{{ $expense ? 'Guardar cambios' : 'Registrar gasto' }}</x-primary-button>
        <a href="{{ route('expenses.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">Cancelar</a>
    </div>
</div>
