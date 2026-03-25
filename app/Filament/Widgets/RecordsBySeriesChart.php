<?php

namespace App\Filament\Widgets;

use App\Models\DocumentarySeries;
use Filament\Widgets\ChartWidget;

class RecordsBySeriesChart extends ChartWidget
{
    protected static ?string $heading = 'Top Series Documentales — Inventario (FUID)';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = [
        'md' => 3,
        'xl' => 3,
    ];

    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $user         = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin');
        $unitId       = $user?->organizational_unit_id;

        $query = DocumentarySeries::query();

        if (!$isSuperAdmin && $unitId) {
            $query->withCount(['inventoryRecords' => function ($q) use ($unitId) {
                $q->where('organizational_unit_id', $unitId);
            }]);
        } else {
            $query->withCount('inventoryRecords');
        }

        $series = $query
            ->having('inventory_records_count', '>', 0)
            ->orderByDesc('inventory_records_count')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Registros de Inventario',
                    'data'            => $series->pluck('inventory_records_count')->toArray(),
                    'backgroundColor' => [
                        'rgba(14, 165, 233, 0.88)',
                        'rgba(59, 130, 246, 0.88)',
                        'rgba(139, 92, 246, 0.88)',
                        'rgba(16, 185, 129, 0.88)',
                        'rgba(245, 158, 11, 0.88)',
                        'rgba(251, 146, 60, 0.88)',
                        'rgba(236, 72, 153, 0.88)',
                        'rgba(239, 68, 68, 0.88)',
                    ],
                    'hoverOffset'      => 15,
                    'hoverBorderColor' => '#fff',
                    'hoverBorderWidth' => 2,
                ],
            ],
            'labels' => $series->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        $bgFn     = "function(ctx){ var d=document.documentElement.classList.contains('dark'); return d?'rgba(15,23,42,0.97)':'rgba(255,255,255,0.97)'; }";
        $titleFn  = "function(ctx){ var d=document.documentElement.classList.contains('dark'); return d?'#f1f5f9':'#0f172a'; }";
        $bodyFn   = "function(ctx){ var d=document.documentElement.classList.contains('dark'); return d?'#94a3b8':'#475569'; }";
        $borderFn = "function(ctx){ var d=document.documentElement.classList.contains('dark'); return d?'rgba(51,65,85,0.8)':'rgba(226,232,240,1)'; }";

        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'display'  => true,
                    'labels'   => [
                        'padding'   => 12,
                        'boxWidth'  => 14,
                        'boxHeight' => 14,
                        'font'      => ['size' => 11],
                    ],
                ],
                'tooltip' => [
                    'enabled'         => true,
                    'backgroundColor' => $bgFn,
                    'titleColor'      => $titleFn,
                    'bodyColor'       => $bodyFn,
                    'borderColor'     => $borderFn,
                    'borderWidth'     => 1,
                    'padding'         => 12,
                    'cornerRadius'    => 8,
                    'displayColors'   => true,
                    'boxWidth'        => 10,
                    'boxHeight'       => 10,
                    'callbacks'       => [
                        'title' => "function(items){ return items[0]?.label || ''; }",
                        'label' => "function(ctx){ var val=ctx.parsed; var total=ctx.dataset.data.reduce(function(a,b){return a+b;},0); var pct=total>0?(val/total*100).toFixed(1):'0'; return '  '+val+' registros — '+pct+'% del total'; }",
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive'          => true,
        ];
    }
}
