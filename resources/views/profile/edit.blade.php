<x-app-layout title="Mi perfil">
    <x-page-header title="Mi perfil" subtitle="Actualiza tu información personal, contraseña o elimina tu cuenta." />

    <div class="max-w-2xl space-y-6">
        <x-card>
            @include('profile.partials.update-profile-information-form')
        </x-card>

        <x-card>
            @include('profile.partials.update-password-form')
        </x-card>

        <x-card>
            @include('profile.partials.delete-user-form')
        </x-card>
    </div>
</x-app-layout>
