<?php

namespace App\Filament\Widgets;

use App\Models\AdministrativeAct;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestActsWidget extends BaseWidget
{
    protected static ?string $heading = 'Ultimos Actos CCD';

    protected static ?int $sort = 7;

    // protected int | string | array $columnSpan = [
    //     'md' => 1,
    //     'xl' => 1,
    // ];

        protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = AdministrativeAct::query()->latest()->limit(3);

        $user = auth()->user();
        if ($user && !$user->hasRole('super_admin') && $user->organizational_unit_id) {
            $query->where('organizational_unit_id', $user->organizational_unit_id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('filing_number')
                    ->label('Consecutivo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Objeto')
                    ->limit(30),

                Tables\Columns\TextColumn::make('actClassification.name')
                    ->label('Tipo')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->url(fn(AdministrativeAct $record): string => route('filament.admin.resources.administrative-acts.view', $record))
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated(false);
    }
}
