@props(['message' => 'No hay datos todavía.'])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-gray-300 dark:border-gray-600 py-10 text-center text-sm text-gray-500 dark:text-gray-400']) }}>
    {{ $message }}
</div>
