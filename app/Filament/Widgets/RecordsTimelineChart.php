<?php

namespace App\Filament\Widgets;

use App\Models\AdministrativeAct;
use App\Models\InventoryRecord;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RecordsTimelineChart extends ChartWidget
{
    protected static ?string $heading = 'Evolución Histórica de Documentos por Año';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $user         = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin');
        $unitId       = $user?->organizational_unit_id;

        $driver      = DB::connection()->getDriverName();
        $currentYear = (int) date('Y');
        $minYear     = 2010;

        if ($driver === 'sqlite') {
            $yearExpressionStart = "strftime('%Y', start_date)";
            $yearExpressionAct   = "strftime('%Y', created_at)";
        } else {
            $yearExpressionStart = 'YEAR(start_date)';
            $yearExpressionAct   = 'YEAR(created_at)';
        }

        $fuidQuery = InventoryRecord::select(
            DB::raw("{$yearExpressionStart} as year"),
            DB::raw('COUNT(*) as count')
        )
            ->whereNotNull('start_date')
            ->where('has_start_date', true)
            ->whereRaw("{$yearExpressionStart} >= ?", [$minYear])
            ->whereRaw("{$yearExpressionStart} <= ?", [$currentYear]);

        if (!$isSuperAdmin && $unitId) {
            $fuidQuery->where('organizational_unit_id', $unitId);
        }

        $fuidData = $fuidQuery
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get()
            ->keyBy('year');

        $ccdQuery = AdministrativeAct::select(
            DB::raw("{$yearExpressionAct} as year"),
            DB::raw('COUNT(*) as count')
        )
            ->whereNotNull('created_at')
            ->whereRaw("{$yearExpressionAct} >= ?", [$minYear])
            ->whereRaw("{$yearExpressionAct} <= ?", [$currentYear]);

        if (!$isSuperAdmin && $unitId) {
            $ccdQuery->where('organizational_unit_id', $unitId);
        }

        $ccdData = $ccdQuery
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get()
            ->keyBy('year');

        $allYears  = $fuidData->keys()->merge($ccdData->keys())->unique()->sort()->values();
        $fuidCounts = $allYears->map(fn($year) => $fuidData->get($year)?->count ?? 0)->toArray();
        $ccdCounts  = $allYears->map(fn($year) => $ccdData->get($year)?->count ?? 0)->toArray();

        return [
            'datasets' => [
                [
                    'label'               => 'Inventario (FUID)',
                    'data'                => $fuidCounts,
                    'borderColor'         => 'rgba(59, 130, 246, 1)',
                    'backgroundColor'     => 'rgba(59, 130, 246, 0.15)',
                    'fill'                => true,
                    'tension'             => 0.4,
                    'pointRadius'         => 5,
                    'pointHoverRadius'    => 8,
                    'pointBackgroundColor'=> 'rgba(59, 130, 246, 1)',
                    'borderWidth'         => 2,
                ],
                [
                    'label'               => 'SUR (CCD)',
                    'data'                => $ccdCounts,
                    'borderColor'         => 'rgba(16, 185, 129, 1)',
                    'backgroundColor'     => 'rgba(16, 185, 129, 0.15)',
                    'fill'                => true,
                    'tension'             => 0.4,
                    'pointRadius'         => 5,
                    'pointHoverRadius'    => 8,
                    'pointBackgroundColor'=> 'rgba(16, 185, 129, 1)',
                    'borderWidth'         => 2,
                ],
            ],
            'labels' => $allYears->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
            {
                plugins: {
                    legend: {
                        display: true,
                        labels: { padding: 15, boxWidth: 14, boxHeight: 14, font: { size: 12 }, usePointStyle: true },
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        backgroundColor: (ctx) => document.documentElement.classList.contains('dark') ? 'rgba(15,23,42,0.97)' : 'rgba(255,255,255,0.97)',
                        titleColor: (ctx) => document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#0f172a',
                        bodyColor: (ctx) => document.documentElement.classList.contains('dark') ? '#94a3b8' : '#475569',
                        borderColor: (ctx) => document.documentElement.classList.contains('dark') ? 'rgba(51,65,85,0.8)' : 'rgba(226,232,240,1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true,
                        boxWidth: 10,
                        boxHeight: 10,
                        callbacks: {
                            title: (items) => items[0]?.label ? 'Año ' + items[0].label : '',
                            label: (ctx) => '  ' + ctx.dataset.label + ': ' + ctx.raw + ' documento(s)',
                            footer: (items) => {
                                const total = items.reduce((s, i) => s + i.raw, 0);
                                return total > 0 ? '  Total año: ' + total + ' documento(s)' : '';
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, stepSize: 1 },
                        grid: { color: 'rgba(156, 163, 175, 0.15)' },
                    },
                    x: { grid: { display: false } },
                },
                responsive: true,
                maintainAspectRatio: false,
            }
        JS);
    }
}
