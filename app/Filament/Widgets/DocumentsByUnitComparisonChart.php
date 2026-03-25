<?php

namespace App\Filament\Widgets;

use App\Models\OrganizationalUnit;
use Filament\Widgets\ChartWidget;

class DocumentsByUnitComparisonChart extends ChartWidget
{
    protected static ?string $heading = 'Producción Documental por Dependencia (FUID vs. SUR)';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '350px';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

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
                    'label'           => 'Inventario (FUID)',
                    'data'            => $units->pluck('inventory_records_count')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.80)',
                    'borderColor'     => 'rgba(59, 130, 246, 1)',
                    'borderWidth'     => 1,
                    'borderRadius'    => 4,
                    'hoverBackgroundColor' => 'rgba(37, 99, 235, 0.95)',
                ],
                [
                    'label'           => 'SUR (CCD)',
                    'data'            => $units->pluck('administrative_acts_count')->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.80)',
                    'borderColor'     => 'rgba(16, 185, 129, 1)',
                    'borderWidth'     => 1,
                    'borderRadius'    => 4,
                    'hoverBackgroundColor' => 'rgba(5, 150, 105, 0.95)',
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
        $bgFn     = "function(ctx){ var d=document.documentElement.classList.contains('dark'); return d?'rgba(15,23,42,0.97)':'rgba(255,255,255,0.97)'; }";
        $titleFn  = "function(ctx){ var d=document.documentElement.classList.contains('dark'); return d?'#f1f5f9':'#0f172a'; }";
        $bodyFn   = "function(ctx){ var d=document.documentElement.classList.contains('dark'); return d?'#94a3b8':'#475569'; }";
        $borderFn = "function(ctx){ var d=document.documentElement.classList.contains('dark'); return d?'rgba(51,65,85,0.8)':'rgba(226,232,240,1)'; }";

        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'labels'  => [
                        'padding'   => 15,
                        'boxWidth'  => 14,
                        'boxHeight' => 14,
                        'font'      => ['size' => 12],
                    ],
                ],
                'tooltip' => [
                    'enabled'         => true,
                    'mode'            => 'index',
                    'intersect'       => false,
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
                        'label' => "function(ctx){ return '  '+ctx.dataset.label+': '+ctx.raw+' documento(s)'; }",
                        'footer' => "function(items){ var total=items.reduce(function(s,i){return s+i.raw;},0); return total>0?'  Total dependencia: '+total+' documento(s)':''; }",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => ['precision' => 0],
                    'grid'        => ['color' => 'rgba(156, 163, 175, 0.15)'],
                ],
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => [
                        'maxRotation' => 35,
                        'font'        => ['size' => 11],
                    ],
                ],
            ],
            'responsive'          => true,
            'maintainAspectRatio' => false,
        ];
    }
}
