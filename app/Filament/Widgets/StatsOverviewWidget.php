<?php

namespace App\Filament\Widgets;

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
        $totalRecords = InventoryRecord::count();
        $recordsThisMonth = InventoryRecord::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $recordsLastMonth = InventoryRecord::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $percentChange = $recordsLastMonth > 0
            ? round((($recordsThisMonth - $recordsLastMonth) / $recordsLastMonth) * 100, 1)
            : 0;

        return [
            Stat::make('Total Registros', number_format($totalRecords))
                ->description($percentChange >= 0 ? "+{$percentChange}% desde el mes pasado" : "{$percentChange}% desde el mes pasado")
                ->descriptionIcon($percentChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($percentChange >= 0 ? 'success' : 'danger')
                ->chart([7, 3, 4, 5, 6, $recordsLastMonth, $recordsThisMonth]),

            Stat::make('Unidades Organizacionales', OrganizationalUnit::where('is_active', true)->count())
                ->description('Unidades activas')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),

            Stat::make('Series Documentales', DocumentarySeries::where('is_active', true)->count())
                ->description('Series registradas')
                ->descriptionIcon('heroicon-m-folder')
                ->color('warning'),

            Stat::make('Usuarios Activos', User::count())
                ->description('Usuarios del sistema')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}
