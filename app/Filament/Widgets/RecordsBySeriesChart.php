<?php

namespace App\Filament\Widgets;

use App\Models\DocumentarySeries;
use Filament\Widgets\ChartWidget;

class RecordsBySeriesChart extends ChartWidget
{
    protected static ?string $heading = 'FUID - Registros por Serie Documental';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $series = DocumentarySeries::withCount('inventoryRecords')
            ->has('inventoryRecords')
            ->orderByDesc('inventory_records_count')
            ->limit(10)
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
                        'rgba(96, 165, 250, 0.6)',
                        'rgba(29, 78, 216, 0.6)',
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
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
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
