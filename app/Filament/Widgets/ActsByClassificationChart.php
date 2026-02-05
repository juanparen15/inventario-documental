<?php

namespace App\Filament\Widgets;

use App\Models\ActClassification;
use Filament\Widgets\ChartWidget;

class ActsByClassificationChart extends ChartWidget
{
    protected static ?string $heading = 'CCD - Actos por Clasificacion';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $classifications = ActClassification::withCount('administrativeActs')
            ->where('is_active', true)
            ->orderByDesc('administrative_acts_count')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Actos',
                    'data' => $classifications->pluck('administrative_acts_count')->toArray(),
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(5, 150, 105, 0.8)',
                        'rgba(4, 120, 87, 0.8)',
                        'rgba(52, 211, 153, 0.8)',
                        'rgba(110, 231, 183, 0.8)',
                    ],
                    'hoverOffset' => 15,
                    'hoverBorderColor' => 'rgba(16, 185, 129, 1)',
                    'hoverBorderWidth' => 3,
                ],
            ],
            'labels' => $classifications->pluck('name')->toArray(),
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
                    'position' => 'bottom',
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
