<x-filament-panels::page>
    @if($this->hasErrors())
        <div class="space-y-4">
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content p-6">
                    <div class="flex items-center gap-x-3 mb-4">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-warning-500" />
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                            Errores encontrados durante la importación
                        </h2>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Los siguientes registros no pudieron ser importados debido a errores de validación.
                        Corrija los datos en su archivo y vuelva a intentar la importación.
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-white/10">
                                    <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">
                                        Fila
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-950 dark:text-white">
                                        Errores
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                @foreach($this->errors as $row => $rowErrors)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-3 font-medium text-gray-950 dark:text-white whitespace-nowrap">
                                            {{ $row }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                            <ul class="list-disc list-inside space-y-1">
                                                @foreach($rowErrors as $error)
                                                    <li class="text-danger-600 dark:text-danger-400">{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <div class="flex items-center gap-x-3">
                    <x-heroicon-o-check-circle class="h-6 w-6 text-success-500" />
                    <p class="text-gray-600 dark:text-gray-400">
                        No hay errores de importación para mostrar.
                    </p>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
