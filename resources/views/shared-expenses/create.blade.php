<x-app-layout title="Nuevo gasto compartido">
    <x-page-header title="Nuevo gasto compartido" subtitle="Solo tu parte se registra como gasto tuyo; el resto queda como dinero que te deben." />

    <x-card class="max-w-2xl">
        <form
            method="POST"
            action="{{ route('shared-expenses.store') }}"
            x-data="{
                participants: @js(old('participants') ?: [['name' => '', 'amount_owed' => '']]),
                addParticipant() { this.participants.push({ name: '', amount_owed: '' }); },
                removeParticipant(index) { this.participants.splice(index, 1); },
            }"
        >
            @csrf

            <div>
                <x-input-label for="description" value="Descripción" />
                <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description')" placeholder="Ej. Pizza para el grupo" required />
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="total_amount" value="Monto total" />
                    <x-text-input id="total_amount" name="total_amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('total_amount')" required />
                </div>
                <div>
                    <x-input-label for="my_share" value="Tu parte" />
                    <x-text-input id="my_share" name="my_share" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('my_share', 0)" />
                </div>
            </div>
            <x-input-error :messages="$errors->get('total_amount')" class="mt-2" />

            <div class="mt-4">
                <x-input-label for="date" value="Fecha" />
                <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->toDateString())" required />
            </div>

            <div class="mt-4">
                <x-input-label for="category_id" value="Categoría (opcional)" />
                <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Sin categoría</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between">
                    <x-input-label value="¿Quién más debe pagar?" />
                    <button type="button" @click="addParticipant()" class="text-xs font-semibold text-brand-600 hover:underline">+ Agregar persona</button>
                </div>

                <template x-for="(participant, index) in participants" :key="index">
                    <div class="mt-2 flex items-center gap-2">
                        <input type="text" :name="`participants[${index}][name]`" x-model="participant.name" placeholder="Nombre" class="flex-1 rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                        <input type="number" step="0.01" min="0.01" :name="`participants[${index}][amount_owed]`" x-model="participant.amount_owed" placeholder="Monto" class="w-28 rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                        <button type="button" @click="removeParticipant(index)" x-show="participants.length > 1" class="text-red-500 hover:text-red-700">
                            <x-icon name="trash" class="w-4 h-4" />
                        </button>
                    </div>
                </template>
                <x-input-error :messages="$errors->get('participants')" class="mt-2" />
            </div>

            <div class="mt-6">
                <x-primary-button>Guardar</x-primary-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
