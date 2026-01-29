<?php

namespace App\Filament\Widgets;

use App\Models\DocumentarySeries;
use App\Models\InventoryRecord;
use Filament\Widgets\ChartWidget;

class RecordsBySeriesChart extends ChartWidget
{
    protected static ?string $heading = 'Registros por Serie Documental';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $series = DocumentarySeries::withCount('inventoryRecords')
            ->where('is_active', true)
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
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(20, 184, 166, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(6, 182, 212, 0.8)',
                    ],
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
