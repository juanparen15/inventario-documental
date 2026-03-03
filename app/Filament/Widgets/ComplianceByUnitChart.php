<?php

namespace App\Filament\Widgets;

use App\Models\AdministrativeAct;
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

    protected function getOptions(): array
    {
        // Colores para tooltip según modo claro/oscuro (evaluados en cada hover)
        $bgFn     = "function(ctx){ var d=document.documentElement.classList.contains('dark'); return d?'rgba(15,23,42,0.97)':'rgba(255,255,255,0.97)'; }";
        $titleFn  = "function(ctx){ var d=document.documentElement.classList.contains('dark'); return d?'#f1f5f9':'#0f172a'; }";
        $bodyFn   = "function(ctx){ var d=document.documentElement.classList.contains('dark'); return d?'#94a3b8':'#475569'; }";
        $borderFn = "function(ctx){ var d=document.documentElement.classList.contains('dark'); return d?'rgba(51,65,85,0.8)':'rgba(226,232,240,1)'; }";

        return [
            'indexAxis' => 'y',
            'plugins'   => [
                'legend'  => ['display' => false],
                'tooltip' => [
                    'enabled'         => true,
                    'backgroundColor' => $bgFn,
                    'titleColor'      => $titleFn,
                    'bodyColor'       => $bodyFn,
                    'borderColor'     => $borderFn,
                    'borderWidth'     => 1,
                    'padding'         => 12,
                    'cornerRadius'    => 8,
                    'displayColors'   => true,
                    'boxWidth'        => 10,
                    'boxHeight'       => 10,
                    'callbacks'       => [
                        // Título: nombre completo de la unidad (sin truncar)
                        'title' => "function(items){ if(!items.length) return ''; var d=items[0].chart.data; return (d.fullLabels&&d.fullLabels[items[0].dataIndex]) ? d.fullLabels[items[0].dataIndex] : items[0].label; }",
                        // Cuerpo: porcentaje de cumplimiento + conteo con/sin PDF
                        'label' => "function(ctx){ var pct=ctx.raw.toFixed(1); var ds=ctx.dataset; var i=ctx.dataIndex; var total=ds.totalCounts?ds.totalCounts[i]:'?'; var pdf=ds.withPdfCounts?ds.withPdfCounts[i]:'?'; var sin=total!=='?'?(total-pdf):'?'; return ['  Cumplimiento: '+pct+'%','  Con PDF: '+pdf+' de '+total+' actos','  Sin PDF: '+sin]; }",
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'max'         => 100,
                    'ticks'       => [
                        'callback' => "function(v){ return v + '%' }",
                    ],
                    'grid' => ['color' => 'rgba(156, 163, 175, 0.15)'],
                ],
                'y' => [
                    'grid'  => ['display' => false],
                    'ticks' => ['font' => ['size' => 11]],
                ],
            ],
            'responsive'          => true,
            'maintainAspectRatio' => false,
        ];
    }
}
