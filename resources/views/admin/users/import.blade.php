<x-admin-layout title="Importar estudiantes">
    <x-page-header title="Importar estudiantes desde CSV" subtitle="Cada fila crea una cuenta ya aprobada y le envía su contraseña por correo." />

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-3 text-sm text-red-700 dark:text-red-300">
            {{ $errors->first() }}
        </div>
    @endif

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            @if ($faculties)
                <div>
                    <x-input-label for="faculty_id" value="Facultad" />
                    <select id="faculty_id" name="faculty_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 focus:ring-brand-500" required>
                        <option value="">Selecciona una facultad</option>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <x-input-label for="file" value="Archivo CSV" />
                <input id="file" name="file" type="file" accept=".csv,text/csv" required class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-300">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Debe tener las columnas <code>nombre</code> y <code>correo</code> (en ese orden o cualquier otro, se detectan por encabezado).
                    Solo se aceptan correos @upn.edu.pe; si tienes un Excel, guárdalo primero como CSV.
                </p>
            </div>

            <x-primary-button>Importar</x-primary-button>
        </form>
    </x-card>
</x-admin-layout>
