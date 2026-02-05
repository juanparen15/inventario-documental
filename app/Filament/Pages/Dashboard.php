<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ActsByClassificationChart;
use App\Filament\Widgets\DocumentsByUnitComparisonChart;
use App\Filament\Widgets\LatestActsWidget;
use App\Filament\Widgets\LatestRecordsWidget;
use App\Filament\Widgets\RecordsBySeriesChart;
use App\Filament\Widgets\RecordsTimelineChart;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Actions;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Panel de Control';

    protected static ?string $navigationLabel = 'Inicio';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('tutorial')
                ->label('¿Necesitas ayuda?')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->extraAttributes([
                    'data-tour' => 'help-button-dashboard',
                    'onclick' => 'window.iniciarTour(); return false;',
                ]),
        ];
    }

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            RecordsBySeriesChart::class,
            ActsByClassificationChart::class,
            DocumentsByUnitComparisonChart::class,
            RecordsTimelineChart::class,
            LatestRecordsWidget::class,
            LatestActsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
