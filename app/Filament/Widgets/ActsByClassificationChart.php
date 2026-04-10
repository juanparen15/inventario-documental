<?php

namespace App\Filament\Widgets;

use App\Models\DocumentarySeries;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ActsByClassificationChart extends ChartWidget
{
    protected static ?string $heading = 'Top Series Documentales — SUR (CCD)';

    protected static ?int $sort = 3;

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

        $query = DocumentarySeries::query()
            ->where('is_active', true)
            ->where('context', 'ccd');

        if (!$isSuperAdmin && $unitId) {
            $query->withCount(['administrativeActs' => function ($q) use ($unitId) {
                $q->where('organizational_unit_id', $unitId);
            }]);
        } else {
            $query->withCount('administrativeActs');
        }

        $series = $query
            ->having('administrative_acts_count', '>', 0)
            ->orderByDesc('administrative_acts_count')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Registros SUR',
                    'data'            => $series->pluck('administrative_acts_count')->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.88)',
                        'rgba(16, 185, 129, 0.88)',
                        'rgba(245, 158, 11, 0.88)',
                        'rgba(139, 92, 246, 0.88)',
                        'rgba(239, 68, 68, 0.88)',
                        'rgba(236, 72, 153, 0.88)',
                        'rgba(14, 165, 233, 0.88)',
                        'rgba(251, 146, 60, 0.88)',
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

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
            {
                plugins: {
                    legend: {
                        position: 'right',
                        display: true,
                        labels: { padding: 12, boxWidth: 14, boxHeight: 14, font: { size: 11 } },
                    },
                    tooltip: {
                        enabled: true,
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
                            title: (items) => items[0]?.label || '',
                            label: (ctx) => {
                                const val = ctx.parsed;
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? (val / total * 100).toFixed(1) : '0';
                                return '  ' + val + ' registros — ' + pct + '% del total';
                            },
                        },
                    },
                },
                maintainAspectRatio: false,
                responsive: true,
            }
        JS);
    }
}
