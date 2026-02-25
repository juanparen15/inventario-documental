<x-filament-panels::page>

    {{-- ── Período seleccionado ── --}}
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Período: {{ $this->getMonthLabel() }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Total de actos registrados en el período:
            <span class="font-bold text-primary-600">{{ $this->getTotalActs() }}</span>
        </p>
    </div>

    {{-- ── Estadísticas por entidad/unidad/subserie ── --}}
    @php $stats = $this->getStats(); @endphp

    @if(empty($stats))
        <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 text-center text-gray-500">
            No hay actos administrativos registrados en este período.
        </div>
    @else
        @foreach($stats as $entityName => $units)
            <div class="mb-6 rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
                {{-- Encabezado entidad --}}
                <div class="bg-primary-600 px-6 py-3">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wide">
                        {{ $entityName }}
                    </h3>
                </div>

                @foreach($units as $unitName => $subseries)
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 last:border-0">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $unitName }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ array_sum($subseries) }} acto(s)
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach($subseries as $subserieName => $count)
                                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 px-3 py-2 text-center">
                                    <div class="text-lg font-bold text-primary-600">{{ $count }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $subserieName }}">
                                        {{ $subserieName }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif

    {{-- ── Actos pendientes de PDF ── --}}
    @php $pending = $this->getPendingPdf(); @endphp

    <div class="mt-8 rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <div class="bg-danger-600 px-6 py-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-white uppercase tracking-wide">
                Actos sin PDF adjunto
            </h3>
            <span class="rounded-full bg-white/20 px-3 py-0.5 text-xs font-semibold text-white">
                {{ $pending->count() }} pendiente(s)
            </span>
        </div>

        @if($pending->isEmpty())
            <div class="p-6 text-center text-gray-500">
                ✓ Todos los actos tienen PDF adjunto.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Consecutivo</th>
                            <th class="px-4 py-3">Unidad</th>
                            <th class="px-4 py-3">Entidad</th>
                            <th class="px-4 py-3">Objeto / Asunto</th>
                            <th class="px-4 py-3">Fecha registro</th>
                            <th class="px-4 py-3">Días para PDF</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($pending as $act)
                            @php
                                $daysLeft = $act->pdfDaysRemaining();
                                if ($daysLeft < 0) {
                                    $daysBadge = 'Vencido ' . abs($daysLeft) . 'd';
                                    $badgeClass = 'bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300';
                                } elseif ($daysLeft <= 5) {
                                    $daysBadge = $daysLeft . ' días';
                                    $badgeClass = 'bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300';
                                } elseif ($daysLeft <= 15) {
                                    $daysBadge = $daysLeft . ' días';
                                    $badgeClass = 'bg-warning-100 text-warning-700 dark:bg-warning-900 dark:text-warning-300';
                                } else {
                                    $daysBadge = $daysLeft . ' días';
                                    $badgeClass = 'bg-info-100 text-info-700 dark:bg-info-900 dark:text-info-300';
                                }
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 font-mono text-xs">{{ $act->filing_number }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $act->organizationalUnit?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $act->organizationalUnit?->entity?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate" title="{{ $act->subject }}">{{ $act->subject }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $act->created_at->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                        {{ $daysBadge }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-filament-panels::page>
