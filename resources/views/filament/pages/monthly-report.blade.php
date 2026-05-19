<x-filament-panels::page>

@php
    $stats   = $this->getStats();
    $pending = $this->getPendingPdf();
    $key     = $this->selectedMonth . '-' . $this->selectedYear;
@endphp

{{-- ══════════════════════════════════════════════════════════════════
     STATS OVERVIEW (KPIs nativos de Filament)
══════════════════════════════════════════════════════════════════ --}}
@livewire(
    \App\Filament\Widgets\MonthlyStatsOverview::class,
    ['month' => $this->selectedMonth, 'year' => $this->selectedYear],
    key('stats-' . $key)
)

{{-- ══════════════════════════════════════════════════════════════════
     GRÁFICAS INTERACTIVAS — 2 columnas
══════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    @livewire(
        \App\Filament\Widgets\MonthlyTrendChart::class,
        ['month' => $this->selectedMonth, 'year' => $this->selectedYear],
        key('trend-' . $key)
    )
    @livewire(
        \App\Filament\Widgets\ComplianceByUnitChart::class,
        ['month' => $this->selectedMonth, 'year' => $this->selectedYear],
        key('compliance-' . $key)
    )
</div>

{{-- ══════════════════════════════════════════════════════════════════
     DISTRIBUCIÓN POR CLASIFICACIÓN
══════════════════════════════════════════════════════════════════ --}}
<x-filament::section
    heading="Distribución de documentos por clasificación"
    description="{{ mb_strtoupper($this->getMonthLabel()) }}"
    icon="heroicon-o-squares-2x2"
    icon-color="primary"
    :collapsible="true"
>

    @if(empty($stats))
        <div class="flex flex-col items-center justify-center py-10 gap-3 text-gray-400 dark:text-gray-500">
            <x-filament::icon
                icon="heroicon-o-inbox"
                class="w-10 h-10"
            />
            <p class="text-sm">No hay documentos registrados en este período.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($stats as $entityName => $units)
                {{-- Cabecera de entidad --}}
                <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-between bg-primary-600 dark:bg-primary-700 px-4 py-2.5">
                        <div class="flex items-center gap-2">
                            <x-filament::icon
                                icon="heroicon-m-building-office-2"
                                class="w-4 h-4 text-primary-200"
                            />
                            <span class="text-xs font-bold text-white uppercase tracking-wider">
                                {{ $entityName }}
                            </span>
                        </div>
                        @php $entityTotal = array_sum(array_map('array_sum', $units)); @endphp
                        <x-filament::badge color="gray" size="sm" class="bg-white/20 text-white border-0">
                            {{ number_format($entityTotal) }} documentos
                        </x-filament::badge>
                    </div>

                    @foreach($units as $unitName => $subseries)
                        <div class="border-t border-gray-100 dark:border-white/5 bg-white dark:bg-gray-900 px-4 py-3">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <x-filament::icon
                                        icon="heroicon-m-users"
                                        class="w-4 h-4 text-gray-400"
                                    />
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $unitName }}
                                    </span>
                                </div>
                                <x-filament::badge color="primary" size="sm">
                                    {{ number_format(array_sum($subseries)) }}
                                </x-filament::badge>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                                @foreach($subseries as $subserieName => $count)
                                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 ring-1 ring-gray-950/5 dark:ring-white/10 px-3 py-2.5 text-center hover:ring-primary-400 dark:hover:ring-primary-500 transition-shadow">
                                        <div class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                            {{ number_format($count) }}
                                        </div>
                                        <div
                                            class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 leading-tight truncate"
                                            title="{{ $subserieName }}"
                                        >
                                            {{ $subserieName }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

</x-filament::section>

{{-- ══════════════════════════════════════════════════════════════════
     ACTOS SIN PDF — tabla con severidad
══════════════════════════════════════════════════════════════════ --}}
<x-filament::section
    icon="heroicon-o-exclamation-triangle"
    icon-color="danger"
    :collapsible="true"
>
    <x-slot name="heading">
        <div class="flex items-center gap-3">
            <span>Documentos sin PDF adjunto</span>
            @if($pending->isNotEmpty())
                <x-filament::badge color="danger">
                    {{ $pending->count() }} pendiente{{ $pending->count() !== 1 ? 's' : '' }}
                </x-filament::badge>
            @endif
        </div>
    </x-slot>

    <x-slot name="description">
        Histórico acumulado de documentos sin PDF adjunto
    </x-slot>

    @if($pending->isEmpty())
        <div class="flex flex-col items-center justify-center py-10 gap-3 text-success-600 dark:text-success-400">
            <x-filament::icon
                icon="heroicon-o-check-circle"
                class="w-10 h-10"
            />
            <p class="text-sm font-semibold">Todos los documentos tienen PDF adjunto.</p>
        </div>
    @else
        @php
            $vencidos    = $pending->filter(fn($a) => $a->pdfDaysRemaining() < 0);
            $criticos    = $pending->filter(fn($a) => $a->pdfDaysRemaining() >= 0 && $a->pdfDaysRemaining() <= 5);
            $advertencia = $pending->filter(fn($a) => $a->pdfDaysRemaining() > 5 && $a->pdfDaysRemaining() <= 15);
        @endphp

        {{-- Resumen de alertas --}}
        @if($vencidos->count() || $criticos->count() || $advertencia->count())
            <div class="mb-4 flex flex-wrap items-center gap-3 rounded-lg bg-danger-50 dark:bg-danger-950/30 px-4 py-3 ring-1 ring-danger-200 dark:ring-danger-900">
                <x-filament::icon icon="heroicon-m-exclamation-circle" class="w-4 h-4 text-danger-600 shrink-0" />
                @if($vencidos->count())
                    <x-filament::badge color="danger">
                        {{ $vencidos->count() }} vencido{{ $vencidos->count() !== 1 ? 's' : '' }}
                    </x-filament::badge>
                @endif
                @if($criticos->count())
                    <x-filament::badge color="danger">
                        {{ $criticos->count() }} crítico{{ $criticos->count() !== 1 ? 's' : '' }} (≤ 5 días)
                    </x-filament::badge>
                @endif
                @if($advertencia->count())
                    <x-filament::badge color="warning">
                        {{ $advertencia->count() }} por vencer (6–15 días)
                    </x-filament::badge>
                @endif
            </div>
        @endif

        {{-- Tabla --}}
        <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            Consecutivo
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            Unidad / Entidad
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            Objeto / Asunto
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide whitespace-nowrap">
                            Fecha registro
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide text-center">
                            Estado
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($pending->sortBy(fn($a) => $a->pdfDaysRemaining()) as $act)
                        @php
                            $days = $act->pdfDaysRemaining();
                            [$badgeColor, $label] = match (true) {
                                $days < 0    => ['danger',  'Vencido hace ' . abs($days) . 'd'],
                                $days <= 5   => ['danger',  $days . 'd restantes'],
                                $days <= 15  => ['warning', $days . 'd restantes'],
                                default      => ['info',    $days . 'd restantes'],
                            };
                            $rowBg = match (true) {
                                $days < 0   => 'bg-danger-50/40 dark:bg-danger-950/10',
                                $days <= 5  => 'bg-danger-50/20 dark:bg-danger-950/5',
                                $days <= 15 => 'bg-warning-50/20 dark:bg-warning-950/5',
                                default     => '',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-white/5 transition-colors {{ $rowBg }}">
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $act->filing_number }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $act->organizationalUnit?->name ?? '—' }}
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $act->organizationalUnit?->entity?->name ?? '—' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs">
                                <span title="{{ $act->subject }}">{{ Str::limit($act->subject, 60) }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-xs text-gray-700 dark:text-gray-300">{{ $act->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-400">{{ $act->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <x-filament::badge :color="$badgeColor" size="sm">
                                    {{ $label }}
                                </x-filament::badge>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</x-filament::section>

</x-filament-panels::page>
