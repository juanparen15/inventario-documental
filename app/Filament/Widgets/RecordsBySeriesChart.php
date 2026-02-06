<?php

namespace App\Filament\Widgets;

use App\Models\DocumentarySeries;
use Filament\Widgets\ChartWidget;

class RecordsBySeriesChart extends ChartWidget
{
    protected static ?string $heading = 'FUID - Registros por Serie Documental';

    protected static ?int $sort = 2;

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
                    'label' => 'Registros',
                    'data' => $series->pluck('inventory_records_count')->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(37, 99, 235, 0.8)',
                        'rgba(29, 78, 216, 0.8)',
                        'rgba(96, 165, 250, 0.8)',
                        'rgba(147, 197, 253, 0.8)',
                        'rgba(30, 64, 175, 0.8)',
                        'rgba(59, 130, 246, 0.6)',
                        'rgba(37, 99, 235, 0.6)',
                    ],
                    'hoverOffset' => 15,
                    'hoverBorderColor' => 'rgba(59, 130, 246, 1)',
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
