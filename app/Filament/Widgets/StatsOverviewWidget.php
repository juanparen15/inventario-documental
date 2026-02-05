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
        // FUID stats
        $totalRecords = InventoryRecord::count();
        $recordsThisMonth = InventoryRecord::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $recordsLastMonth = InventoryRecord::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $fuidPercentChange = $recordsLastMonth > 0
            ? round((($recordsThisMonth - $recordsLastMonth) / $recordsLastMonth) * 100, 1)
            : 0;

        // CCD stats
        $totalActs = AdministrativeAct::count();
        $actsThisMonth = AdministrativeAct::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $actsLastMonth = AdministrativeAct::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $ccdPercentChange = $actsLastMonth > 0
            ? round((($actsThisMonth - $actsLastMonth) / $actsLastMonth) * 100, 1)
            : 0;

        return [
            Stat::make('Registros del FUID (Formato Unico de Inventario Documental)', number_format($totalRecords))
                ->description($fuidPercentChange >= 0 ? "+{$fuidPercentChange}% desde el mes pasado" : "{$fuidPercentChange}% desde el mes pasado")
                ->descriptionIcon($fuidPercentChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, $recordsLastMonth, $recordsThisMonth]),

            Stat::make('Actos del CCD (Cuadro Clasificación Documental)', number_format($totalActs))
                ->description($ccdPercentChange >= 0 ? "+{$ccdPercentChange}% desde el mes pasado" : "{$ccdPercentChange}% desde el mes pasado")
                ->descriptionIcon($ccdPercentChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color('success')
                ->chart([3, 5, 2, 4, 6, $actsLastMonth, $actsThisMonth]),

            Stat::make('Unidades Organizacionales', OrganizationalUnit::where('is_active', true)->count())
                ->description('Unidades activas')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),

            Stat::make('Series FUID', DocumentarySeries::fuid()->where('is_active', true)->count())
                ->description('Series inventario documental')
                ->descriptionIcon('heroicon-m-folder')
                ->color('primary'),

            Stat::make('Series CCD', DocumentarySeries::ccd()->where('is_active', true)->count())
                ->description('Series cuadro clasificacion')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('success'),

            Stat::make('Usuarios Activos', User::count())
                ->description('Usuarios del sistema')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }
}
