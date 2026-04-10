<?php

namespace App\Filament\Widgets;

use App\Models\AdministrativeAct;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

/**
 * Gráfica de barras: actos registrados en los últimos 6 meses.
 * Embebida en MonthlyReportPage. No aparece en el dashboard.
 */
class MonthlyTrendChart extends ChartWidget
{
    public int $month;
    public int $year;

    protected static ?string $heading        = 'Tendencia de registros — últimos 6 meses';
    protected static ?string $pollingInterval = null;
    protected static bool    $isLazy         = false;
    protected static ?string $maxHeight      = '260px';

    public static function canView(): bool { return false; }

    protected function getData(): array
    {
        $points = collect(range(5, 0))->map(function ($i) {
            $date  = Carbon::createFromDate($this->year, $this->month, 1)->subMonths($i);
            $count = AdministrativeAct::whereNull('deleted_at')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            return [
                'label'     => $date->locale('es')->isoFormat('MMM YYYY'),
                'labelShort'=> $date->locale('es')->isoFormat('MMM YY'),
                'count'     => $count,
            ];
        });

        return [
            'datasets' => [
                [
                    'label'               => 'Actos registrados',
                    'data'                => $points->pluck('count')->all(),
                    'backgroundColor'     => 'rgba(59, 130, 246, 0.80)',
                    'borderColor'         => 'rgba(59, 130, 246, 1)',
                    'borderWidth'         => 1,
                    'borderRadius'        => 5,
                    'hoverBackgroundColor'=> 'rgba(37, 99, 235, 0.95)',
                ],
            ],
            'labels'     => $points->pluck('labelShort')->all(),
            'fullLabels' => $points->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
            {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: (ctx) => document.documentElement.classList.contains('dark') ? 'rgba(15,23,42,0.97)' : 'rgba(255,255,255,0.97)',
                        titleColor: (ctx) => document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#0f172a',
                        bodyColor: (ctx) => document.documentElement.classList.contains('dark') ? '#94a3b8' : '#475569',
                        borderColor: (ctx) => document.documentElement.classList.contains('dark') ? 'rgba(51,65,85,0.8)' : 'rgba(226,232,240,1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            title: (items) => {
                                if (!items.length) return '';
                                const d = items[0].chart.data;
                                return (d.fullLabels && d.fullLabels[items[0].dataIndex])
                                    ? d.fullLabels[items[0].dataIndex]
                                    : items[0].label;
                            },
                            label: (ctx) => {
                                const n = ctx.raw;
                                return '  ' + n + (n === 1 ? ' acto registrado' : ' actos registrados');
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0 },
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
