<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header px-6 py-4 border-b border-gray-200 dark:border-white/10">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Active las unidades organizacionales que pueden ver y usar los botones
                <strong>Descargar Plantilla</strong> e <strong>Importar</strong>
                en el listado de Sistema unificado de registro.
            </p>
        </div>

        <div class="fi-section-content p-6">
            @if(empty($units))
                <p class="text-sm text-gray-500 dark:text-gray-400">No hay unidades organizacionales activas.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-white/10">
                                <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">
                                    Unidad Organizacional
                                </th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white w-32">
                                    Código
                                </th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-950 dark:text-white w-40">
                                    Puede Importar
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach($units as $unit)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">
                                        {{ $unit['name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">
                                        {{ $unit['code'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            wire:click="toggleImport({{ $unit['id'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleImport({{ $unit['id'] }})"
                                            type="button"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50
                                                {{ $unit['can_import'] ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}"
                                            role="switch"
                                            aria-checked="{{ $unit['can_import'] ? 'true' : 'false' }}"
                                        >
                                            <span class="sr-only">{{ $unit['can_import'] ? 'Desactivar' : 'Activar' }} permiso para {{ $unit['name'] }}</span>
                                            <span
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out
                                                    {{ $unit['can_import'] ? 'translate-x-5' : 'translate-x-0' }}"
                                            ></span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
