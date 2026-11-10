@props(['for' => 'password'])

{{--
    Listens to the password input's own "input" event by id instead of
    wrapping it, so it drops into any existing form (register, cambio
    forzado, restablecer, perfil, alta de decano) without restructuring
    that input's markup — it just needs a matching id.
--}}
<ul
    x-data="{
        value: '',
        get hasLength() { return this.value.length >= 6 },
        get hasUpper() { return /[A-Z]/.test(this.value) },
        get hasLower() { return /[a-z]/.test(this.value) },
        get hasSpecial() { return /[^A-Za-z0-9]/.test(this.value) },
    }"
    x-init="
        const el = document.getElementById('{{ $for }}');
        if (el) {
            value = el.value;
            el.addEventListener('input', () => value = el.value);
        }
    "
    class="mt-2 space-y-0.5 text-xs text-gray-500 dark:text-gray-400"
>
    <li :class="hasLength && 'text-brand-600 dark:text-brand-400'">
        <span x-text="hasLength ? '✓' : '○'"></span> Al menos 6 caracteres
    </li>
    <li :class="hasUpper && 'text-brand-600 dark:text-brand-400'">
        <span x-text="hasUpper ? '✓' : '○'"></span> Una letra mayúscula
    </li>
    <li :class="hasLower && 'text-brand-600 dark:text-brand-400'">
        <span x-text="hasLower ? '✓' : '○'"></span> Una letra minúscula
    </li>
    <li :class="hasSpecial && 'text-brand-600 dark:text-brand-400'">
        <span x-text="hasSpecial ? '✓' : '○'"></span> Un carácter especial (ej. !@#$%)
    </li>
</ul>
