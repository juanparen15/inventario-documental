<?php

namespace App\Filament\Widgets;

use App\Models\OrganizationalUnit;
use Filament\Widgets\ChartWidget;

class RecordsByUnitChart extends ChartWidget
{
    protected static ?string $heading = 'Registros por Unidad Organizacional';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $units = OrganizationalUnit::withCount('inventoryRecords')
            ->where('is_active', true)
            ->orderByDesc('inventory_records_count')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Registros',
                    'data' => $units->pluck('inventory_records_count')->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(20, 184, 166, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                    ],
                ],
            ],
            'labels' => $units->pluck('name')->toArray(),
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
                ],
            ],
        ];
    }
}
