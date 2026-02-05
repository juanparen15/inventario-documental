<?php

namespace App\Filament\Widgets;

use App\Models\InventoryRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestRecordsWidget extends BaseWidget
{
    protected static ?string $heading = 'Ultimos Registros FUID';

    protected static ?int $sort = 6;

    // protected int | string | array $columnSpan = [
    //     'md' => 2,
    //     'xl' => 2,
    // ];

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = InventoryRecord::query()->latest()->limit(5);

        $user = auth()->user();
        if ($user && !$user->hasRole('super_admin') && $user->organizational_unit_id) {
            $query->where('organizational_unit_id', $user->organizational_unit_id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Codigo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Unidad Documental')
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
                    ->url(fn(InventoryRecord $record): string => route('filament.admin.resources.inventory-records.edit', $record))
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated(false);
    }
}
