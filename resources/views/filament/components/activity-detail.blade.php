@php use App\Support\ActivityFormatter; @endphp

<div class="space-y-5 py-2">

    {{-- Cabecera: evento + recurso + registro --}}
    <div class="flex flex-wrap items-center gap-3">
        <x-filament::badge :color="$eventConfig['color']" :icon="$eventConfig['icon']">
            {{ $eventConfig['label'] }}
        </x-filament::badge>
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $subjectLabel }}</span>
        <span class="text-sm text-gray-500 dark:text-gray-400">— {{ $identifier }}</span>
    </div>

    {{-- Quién, dónde y cuándo --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="flex items-start gap-2">
            <x-heroicon-o-user class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Quién</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                    {{ $activity->causer?->name ?? 'Sistema' }}
                </p>
                @if($activity->causer?->organizationalUnit?->name)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $activity->causer->organizationalUnit->name }}
                    </p>
                @endif
            </div>
        </div>

        <div class="flex items-start gap-2">
            <x-heroicon-o-clock class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Cuándo</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                    {{ $activity->created_at->format('d/m/Y') }}
                    <span class="text-gray-500">a las</span>
                    {{ $activity->created_at->format('H:i:s') }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $activity->created_at->diffForHumans() }}
                </p>
            </div>
        </div>

        @if($activity->properties?->get('ip'))
        <div class="flex items-start gap-2">
            <x-heroicon-o-computer-desktop class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Desde la IP</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                    {{ $activity->properties->get('ip') }}
                </p>
            </div>
        </div>
        @endif

        <div class="flex items-start gap-2">
            <x-heroicon-o-information-circle class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Qué se hizo</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                    {{ $activity->description }}
                </p>
            </div>
        </div>
    </div>

    @php
        $props             = $activity->properties?->toArray() ?? [];
        $oldValues         = $props['old'] ?? [];
        $newValues         = $props['attributes'] ?? [];
        $extras            = collect($props)->except(['old', 'attributes', 'ip'])->toArray();
        $isUpdate          = !empty($oldValues) && !empty($newValues);
        $isCreatedDeleted  = !empty($newValues) && empty($oldValues);
    @endphp

    {{-- Información adicional del evento (PDF subido, razón, etc.) --}}
    @if(!empty($extras))
    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg space-y-2">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Información adicional</p>
        @foreach($extras as $key => $value)
            @if(!is_null($value) && $value !== '')
            <div class="flex flex-wrap gap-2 text-sm">
                <span class="font-medium text-gray-600 dark:text-gray-300 min-w-40">
                    {{ ActivityFormatter::fieldLabel($key) }}:
                </span>
                <span class="text-gray-800 dark:text-gray-100">
                    {{ is_bool($value) ? ($value ? 'Sí' : 'No') : $value }}
                </span>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- CAMBIOS: Antes → Después (evento 'updated') --}}
    @if($isUpdate)
    <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">
            Cambios realizados en el registro
        </p>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase w-1/4">
                            Campo
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-danger-500 uppercase w-5/12">
                            Valor anterior
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-success-600 uppercase w-5/12">
                            Valor nuevo
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($oldValues as $field => $oldVal)
                        @if(!ActivityFormatter::shouldSkip($field))
                        @php $newVal = $newValues[$field] ?? null; @endphp
                        <tr class="bg-white dark:bg-gray-800">
                            <td class="px-4 py-2.5 font-medium text-gray-700 dark:text-gray-300">
                                {{ ActivityFormatter::fieldLabel($field) }}
                            </td>
                            <td class="px-4 py-2.5 text-danger-600 dark:text-danger-400 break-words">
                                {{ ActivityFormatter::fieldValue($field, $oldVal) }}
                            </td>
                            <td class="px-4 py-2.5 text-success-700 dark:text-success-400 break-words">
                                {{ ActivityFormatter::fieldValue($field, $newVal) }}
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- VALORES al crear o eliminar --}}
    @elseif($isCreatedDeleted)
    @php
        $formatted = ActivityFormatter::formatProperties($newValues);
    @endphp
    @if(!empty($formatted))
    <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">
            @if($activity->event === 'created')
                Datos registrados
            @else
                Datos al momento de la eliminación
            @endif
        </p>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase w-1/3">
                            Campo
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase">
                            Valor
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($formatted as $label => $value)
                    <tr class="bg-white dark:bg-gray-800">
                        <td class="px-4 py-2.5 font-medium text-gray-700 dark:text-gray-300">
                            {{ $label }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-800 dark:text-gray-100 break-words">
                            {{ $value }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endif

</div>
