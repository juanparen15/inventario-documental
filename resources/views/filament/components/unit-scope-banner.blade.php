<div class="fi-section rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 px-5 py-4 mb-4 flex items-start gap-3">
    <div class="mt-0.5 shrink-0 text-blue-500 dark:text-blue-400">
        <x-heroicon-o-information-circle class="w-5 h-5" />
    </div>
    <div>
        <p class="text-sm font-semibold text-blue-800 dark:text-blue-200">
            Mostrando registros de: {{ $unitName }}
        </p>
        <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">
            Entidad: {{ $entityName }} &mdash; Total de registros en tu unidad: <strong>{{ number_format($count) }}</strong>
        </p>
        <p class="text-xs text-blue-500 dark:text-blue-500 mt-1">
            Solo ves los registros asignados a tu unidad organizacional.
        </p>
    </div>
</div>
