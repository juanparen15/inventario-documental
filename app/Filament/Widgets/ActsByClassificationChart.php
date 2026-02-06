<?php

namespace App\Filament\Widgets;

use App\Models\DocumentarySeries;
use Filament\Widgets\ChartWidget;

class ActsByClassificationChart extends ChartWidget
{
    protected static ?string $heading = 'CCD - Actos por Serie Documental';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'md' => 3,
        'xl' => 3,
    ];

    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin');
        $unitId = $user?->organizational_unit_id;

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
                    'label' => 'Actos',
                    'data' => $series->pluck('administrative_acts_count')->toArray(),
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(5, 150, 105, 0.8)',
                        'rgba(4, 120, 87, 0.8)',
                        'rgba(52, 211, 153, 0.8)',
                        'rgba(110, 231, 183, 0.8)',
                        'rgba(16, 185, 129, 0.6)',
                        'rgba(5, 150, 105, 0.6)',
                        'rgba(4, 120, 87, 0.6)',
                    ],
                    'hoverOffset' => 15,
                    'hoverBorderColor' => 'rgba(16, 185, 129, 1)',
                    'hoverBorderWidth' => 3,
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
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'display' => true,
                    'labels' => [
                        'padding' => 10,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }
}
