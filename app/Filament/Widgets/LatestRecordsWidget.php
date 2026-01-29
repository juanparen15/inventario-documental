<?php

namespace App\Filament\Widgets;

use App\Models\InventoryRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestRecordsWidget extends BaseWidget
{
    protected static ?string $heading = 'Últimos Registros';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InventoryRecord::query()
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Código')
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->limit(40),

                Tables\Columns\TextColumn::make('organizationalUnit.name')
                    ->label('Unidad'),

                Tables\Columns\TextColumn::make('documentarySeries.name')
                    ->label('Serie'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->url(fn (InventoryRecord $record): string => route('filament.admin.resources.inventory-records.edit', $record))
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated(false);
    }
}
