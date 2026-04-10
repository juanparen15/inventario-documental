<?php

namespace App\Filament\Widgets;

use App\Models\AdministrativeAct;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

/**
 * Gráfica horizontal de barras: cumplimiento PDF por unidad organizacional.
 * Embebida en MonthlyReportPage. No aparece en el dashboard.
 */
class ComplianceByUnitChart extends ChartWidget
{
    public int $month;
    public int $year;

    protected static ?string $heading        = 'Cumplimiento PDF por unidad';
    protected static ?string $pollingInterval = null;
    protected static bool    $isLazy         = false;
    protected static ?string $maxHeight      = '260px';

    public static function canView(): bool { return false; }

    protected function getData(): array
    {
        $allActs = AdministrativeAct::with('organizationalUnit')
            ->whereNull('deleted_at')
            ->get(['organizational_unit_id', 'attachments', 'confidential_attachments']);

        $byUnit = [];
        foreach ($allActs as $act) {
            $unit = $act->organizationalUnit?->name ?? 'Sin unidad';
            $byUnit[$unit]['total'] = ($byUnit[$unit]['total'] ?? 0) + 1;
            if (! $act->lacksPdf()) {
                $byUnit[$unit]['conPdf'] = ($byUnit[$unit]['conPdf'] ?? 0) + 1;
            }
        }

        $items = collect($byUnit)
            ->map(fn($data, $unit) => [
                'unit'       => $unit,
                'total'      => $data['total'],
                'conPdf'     => $data['conPdf'] ?? 0,
                'compliance' => $data['total'] > 0
                    ? round((($data['conPdf'] ?? 0) / $data['total']) * 100, 1)
                    : 100.0,
            ])
            ->sortByDesc('total')
            ->take(10)
            ->values();

        $colors = $items->map(fn($item) => match (true) {
            $item['compliance'] >= 90 => 'rgba(34, 197, 94, 0.85)',
            $item['compliance'] >= 70 => 'rgba(245, 158, 11, 0.85)',
            default                   => 'rgba(239, 68, 68, 0.85)',
        })->all();

        $hoverColors = $items->map(fn($item) => match (true) {
            $item['compliance'] >= 90 => 'rgba(22, 163, 74, 0.95)',
            $item['compliance'] >= 70 => 'rgba(217, 119, 6, 0.95)',
            default                   => 'rgba(220, 38, 38, 0.95)',
        })->all();

        return [
            'datasets' => [
                [
                    'label'               => 'Cumplimiento %',
                    'data'                => $items->pluck('compliance')->all(),
                    // Datos extra accesibles en los callbacks del tooltip
                    'totalCounts'         => $items->pluck('total')->all(),
                    'withPdfCounts'       => $items->pluck('conPdf')->all(),
                    'backgroundColor'     => $colors,
                    'borderColor'         => $colors,
                    'hoverBackgroundColor'=> $hoverColors,
                    'borderWidth'         => 0,
                    'borderRadius'        => 4,
                ],
            ],
            // Labels cortos para el eje Y (truncados), nombres completos para tooltip
            'labels'     => $items->map(fn($item) =>
                mb_strlen($item['unit']) > 30
                    ? mb_substr($item['unit'], 0, 28) . '…'
                    : $item['unit']
            )->all(),
            'fullLabels' => $items->pluck('unit')->all(),
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
                indexAxis: 'y',
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
                        displayColors: true,
                        boxWidth: 10,
                        boxHeight: 10,
                        callbacks: {
                            title: (items) => {
                                if (!items.length) return '';
                                const d = items[0].chart.data;
                                return (d.fullLabels && d.fullLabels[items[0].dataIndex])
                                    ? d.fullLabels[items[0].dataIndex]
                                    : items[0].label;
                            },
                            label: (ctx) => {
                                const pct = ctx.raw.toFixed(1);
                                const ds = ctx.dataset;
                                const i = ctx.dataIndex;
                                const total = ds.totalCounts ? ds.totalCounts[i] : '?';
                                const pdf = ds.withPdfCounts ? ds.withPdfCounts[i] : '?';
                                const sin = total !== '?' ? (total - pdf) : '?';
                                return ['  Cumplimiento: ' + pct + '%', '  Con PDF: ' + pdf + ' de ' + total + ' actos', '  Sin PDF: ' + sin];
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { callback: (v) => v + '%' },
                        grid: { color: 'rgba(156, 163, 175, 0.15)' },
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } },
                    },
                },
                responsive: true,
                maintainAspectRatio: false,
            }
        JS);
    }
}
