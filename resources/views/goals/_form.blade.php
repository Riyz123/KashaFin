@props(['goal' => null])

<div class="space-y-5">
    <div>
        <x-input-label for="name" value="Nombre de la meta" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $goal->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="target_amount" value="Monto objetivo" />
        <x-text-input id="target_amount" name="target_amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('target_amount', $goal->target_amount ?? '')" required />
        <x-input-error :messages="$errors->get('target_amount')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="target_date" value="Fecha límite (opcional)" />
        <x-text-input id="target_date" name="target_date" type="date" class="mt-1 block w-full" :value="old('target_date', optional($goal?->target_date)->toDateString())" />
        <x-input-error :messages="$errors->get('target_date')" class="mt-2" />
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>{{ $goal ? 'Guardar cambios' : 'Crear meta' }}</x-primary-button>
        <a href="{{ route('goals.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">Cancelar</a>
    </div>
</div>
