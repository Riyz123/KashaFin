<x-app-layout title="Nuevo gasto compartido">
    <x-page-header title="Nuevo gasto compartido" subtitle="Solo tu parte se registra como gasto tuyo; cada participante debe aceptar antes de que cuente." />

    <x-card class="max-w-2xl">
        <form
            method="POST"
            action="{{ route('shared-expenses.store') }}"
            x-data="{
                participants: [{ user_id: null, label: '', amount_owed: '', daily_commitment: '' }],
                search: '', results: [], activeIndex: null, searching: false,
                addParticipant() { this.participants.push({ user_id: null, label: '', amount_owed: '', daily_commitment: '' }); },
                removeParticipant(index) { this.participants.splice(index, 1); },
                async runSearch(index) {
                    this.activeIndex = index;
                    const q = this.participants[index].label;
                    if (q.length < 2) { this.results = []; return; }
                    this.searching = true;
                    const res = await fetch(`{{ route('shared-expenses.search-users') }}?q=${encodeURIComponent(q)}`);
                    this.results = await res.json();
                    this.searching = false;
                },
                pick(index, user) {
                    this.participants[index].user_id = user.id;
                    this.participants[index].label = `${user.name} (${user.email})`;
                    this.results = [];
                    this.activeIndex = null;
                },
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
            <x-input-error :messages="$errors->get('participants')" class="mt-2" />

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
                    <x-input-label value="¿Con quién lo divides?" />
                    <button type="button" @click="addParticipant()" class="text-xs font-semibold text-brand-600 hover:underline">+ Agregar persona</button>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Busca por nombre o correo institucional — solo cuentas reales de KashaFin, cada una recibirá una invitación que debe aceptar.
                </p>

                <template x-for="(participant, index) in participants" :key="index">
                    <div class="mt-3 rounded-lg border border-gray-100 p-3 dark:border-gray-700">
                        <div class="relative">
                            <input
                                type="text"
                                x-model="participant.label"
                                @input="runSearch(index)"
                                @focus="activeIndex = index"
                                placeholder="Nombre o correo..."
                                class="w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                autocomplete="off"
                            >
                            <input type="hidden" :name="`participants[${index}][user_id]`" :value="participant.user_id">

                            <div
                                x-show="activeIndex === index && results.length > 0"
                                x-cloak
                                class="absolute z-10 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                            >
                                <template x-for="user in results" :key="user.id">
                                    <button
                                        type="button"
                                        @click="pick(index, user)"
                                        class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        <span x-text="user.name"></span>
                                        <span class="text-gray-400" x-text="`(${user.email})`"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">Le toca pagar</label>
                                <input type="number" step="0.01" min="0.01" :name="`participants[${index}][amount_owed]`" x-model="participant.amount_owed" placeholder="Monto total" class="mt-0.5 w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">Cuota diaria sugerida (opcional)</label>
                                <input type="number" step="0.01" min="0.01" :name="`participants[${index}][daily_commitment]`" x-model="participant.daily_commitment" placeholder="Ej. 5 soles/día" class="mt-0.5 w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            </div>
                        </div>

                        <button type="button" @click="removeParticipant(index)" x-show="participants.length > 1" class="mt-2 text-xs text-red-500 hover:text-red-700">
                            Quitar
                        </button>
                    </div>
                </template>
            </div>

            <div class="mt-6">
                <x-primary-button>Guardar</x-primary-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
