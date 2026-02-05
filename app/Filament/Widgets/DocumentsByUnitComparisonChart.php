<?php

namespace App\Filament\Widgets;

use App\Models\OrganizationalUnit;
use Filament\Widgets\ChartWidget;

class DocumentsByUnitComparisonChart extends ChartWidget
{
    protected static ?string $heading = 'Comparativo por Unidad: FUID vs CCD';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $units = OrganizationalUnit::withCount(['inventoryRecords', 'administrativeActs'])
            ->where('is_active', true)
            ->orderByDesc('inventory_records_count')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'FUID - Registros',
                    'data' => $units->pluck('inventory_records_count')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'CCD - Actos',
                    'data' => $units->pluck('administrative_acts_count')->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $units->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'labels' => [
                        'padding' => 15,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
