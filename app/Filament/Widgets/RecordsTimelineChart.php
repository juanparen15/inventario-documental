<?php

namespace App\Filament\Widgets;

use App\Models\AdministrativeAct;
use App\Models\InventoryRecord;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RecordsTimelineChart extends ChartWidget
{
    protected static ?string $heading = 'Linea de Tiempo: FUID y CCD';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin');
        $unitId = $user?->organizational_unit_id;

        $driver = DB::connection()->getDriverName();
        $currentYear = (int) date('Y');
        $minYear = 2010;

        if ($driver === 'sqlite') {
            $yearExpressionStart = "strftime('%Y', start_date)";
            $yearExpressionAct = "strftime('%Y', created_at)";
        } else {
            $yearExpressionStart = 'YEAR(start_date)';
            $yearExpressionAct = 'YEAR(created_at)';
        }

        // FUID data
        $fuidQuery = InventoryRecord::select(
            DB::raw("{$yearExpressionStart} as year"),
            DB::raw('COUNT(*) as count')
        )
            ->whereNotNull('start_date')
            ->where('has_start_date', true)
            ->whereRaw("{$yearExpressionStart} >= ?", [$minYear])
            ->whereRaw("{$yearExpressionStart} <= ?", [$currentYear]);

        if (!$isSuperAdmin && $unitId) {
            $fuidQuery->where('organizational_unit_id', $unitId);
        }

        $fuidData = $fuidQuery
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get()
            ->keyBy('year');

        // CCD data
        $ccdQuery = AdministrativeAct::select(
            DB::raw("{$yearExpressionAct} as year"),
            DB::raw('COUNT(*) as count')
        )
            ->whereNotNull('created_at')
            ->whereRaw("{$yearExpressionAct} >= ?", [$minYear])
            ->whereRaw("{$yearExpressionAct} <= ?", [$currentYear]);

        if (!$isSuperAdmin && $unitId) {
            $ccdQuery->where('organizational_unit_id', $unitId);
        }

        $ccdData = $ccdQuery
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get()
            ->keyBy('year');

        // Merge years from both datasets
        $allYears = $fuidData->keys()->merge($ccdData->keys())->unique()->sort()->values();

        $fuidCounts = $allYears->map(fn($year) => $fuidData->get($year)?->count ?? 0)->toArray();
        $ccdCounts = $allYears->map(fn($year) => $ccdData->get($year)?->count ?? 0)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'FUID - Registros',
                    'data' => $fuidCounts,
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 7,
                ],
                [
                    'label' => 'CCD - Actos',
                    'data' => $ccdCounts,
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 7,
                ],
            ],
            'labels' => $allYears->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
