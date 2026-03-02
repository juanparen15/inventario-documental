<?php

namespace App\Filament\Widgets;

use App\Models\AdministrativeAct;
use App\Models\DocumentarySeries;
use App\Models\InventoryRecord;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin');
        $unitId = $user?->organizational_unit_id;

        // Base queries filtered by unit for non-super_admin
        $recordsQuery = InventoryRecord::query();
        $actsQuery = AdministrativeAct::query();

        if (!$isSuperAdmin && $unitId) {
            $recordsQuery->where('organizational_unit_id', $unitId);
            $actsQuery->where('organizational_unit_id', $unitId);
        }

        // FUID — últimos 7 meses para el mini-gráfico
        $fuidMonthly = collect(range(6, 0))->map(fn($i) => (clone $recordsQuery)
            ->whereMonth('created_at', now()->subMonths($i)->month)
            ->whereYear('created_at', now()->subMonths($i)->year)
            ->count()
        )->values()->toArray();

        $totalRecords     = (clone $recordsQuery)->count();
        $recordsThisMonth = $fuidMonthly[6];
        $recordsLastMonth = $fuidMonthly[5];
        $fuidPercentChange = $recordsLastMonth > 0
            ? round((($recordsThisMonth - $recordsLastMonth) / $recordsLastMonth) * 100, 1)
            : ($recordsThisMonth > 0 ? 100.0 : 0.0);

        // CCD — últimos 7 meses para el mini-gráfico
        $ccdMonthly = collect(range(6, 0))->map(fn($i) => (clone $actsQuery)
            ->whereMonth('created_at', now()->subMonths($i)->month)
            ->whereYear('created_at', now()->subMonths($i)->year)
            ->count()
        )->values()->toArray();

        $totalActs     = (clone $actsQuery)->count();
        $actsThisMonth = $ccdMonthly[6];
        $actsLastMonth = $ccdMonthly[5];
        $ccdPercentChange = $actsLastMonth > 0
            ? round((($actsThisMonth - $actsLastMonth) / $actsLastMonth) * 100, 1)
            : ($actsThisMonth > 0 ? 100.0 : 0.0);

        $stats = [
            Stat::make('Registros del FUID', number_format($totalRecords))
                ->description(
                    $fuidPercentChange >= 0
                        ? "+{$fuidPercentChange}% respecto al mes anterior"
                        : "{$fuidPercentChange}% respecto al mes anterior"
                )
                ->descriptionIcon($fuidPercentChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color('primary')
                ->chart($fuidMonthly),

            Stat::make('Actos Administrativos', number_format($totalActs))
                ->description(
                    $ccdPercentChange >= 0
                        ? "+{$ccdPercentChange}% respecto al mes anterior"
                        : "{$ccdPercentChange}% respecto al mes anterior"
                )
                ->descriptionIcon($ccdPercentChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color('success')
                ->chart($ccdMonthly),
        ];

        // Solo super_admin ve estas estadisticas globales
        if ($isSuperAdmin) {
            $stats[] = Stat::make('Unidades Organizacionales', OrganizationalUnit::where('is_active', true)->count())
                ->description('Unidades activas')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info');

            $stats[] = Stat::make('Series FUID', DocumentarySeries::fuid()->where('is_active', true)->count())
                ->description('Series inventario documental')
                ->descriptionIcon('heroicon-m-folder')
                ->color('primary');

            $stats[] = Stat::make('Series CCD', DocumentarySeries::ccd()->where('is_active', true)->count())
                ->description('Series cuadro clasificacion')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('success');

            $stats[] = Stat::make('Usuarios Activos', User::count())
                ->description('Usuarios del sistema')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning');
        }

        return $stats;
    }
}
