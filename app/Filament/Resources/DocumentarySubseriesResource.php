<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentarySubseriesResource\Pages;
use App\Models\CcdEntry;
use App\Models\DocumentarySeries;
use App\Models\DocumentarySubseries;
use App\Models\OrganizationalUnit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentarySubseriesResource extends Resource
{
    protected static ?string $model = DocumentarySubseries::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationGroup = 'Tabla de Retencion Documental';

    protected static ?string $modelLabel = 'Subserie Documental';

    protected static ?string $pluralModelLabel = 'Subseries Documentales';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('documentary_series_id')
                            ->label('Serie Documental')
                            ->relationship('documentarySeries', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $series = DocumentarySeries::find($state);
                                    $set('context', $series?->context ?? 'ccd');
                                }
                            }),

                        Forms\Components\TextInput::make('code')
                            ->label('Codigo')
                            ->required()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripcion')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),

                        Forms\Components\Select::make('context')
                            ->label('Contexto')
                            ->options(DocumentarySubseries::CONTEXTS)
                            ->disabled()
                            ->dehydrated()
                            ->default(fn(Get $get) => DocumentarySeries::find($get('documentary_series_id'))?->context ?? 'ccd')
                            ->helperText('Heredado automaticamente de la serie padre.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Unidades Organizacionales')
                    ->description('Seleccione las unidades que tendran acceso a esta subserie')
                    ->schema([
                        Forms\Components\CheckboxList::make('organizational_units')
                            ->label('')
                            ->options(OrganizationalUnit::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->columns(2)
                            ->searchable()
                            ->bulkToggleable()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    $unitIds = CcdEntry::where('documentary_series_id', $record->documentary_series_id)
                                        ->where('documentary_subseries_id', $record->id)
                                        ->pluck('organizational_unit_id')
                                        ->toArray();
                                    $component->state($unitIds);
                                }
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Codigo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('documentarySeries.name')
                    ->label('Serie')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('context')
                    ->label('Contexto')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => DocumentarySubseries::CONTEXTS[$state] ?? $state)
                    ->color(fn(string $state): string => match ($state) {
                        'fuid' => 'info',
                        'ccd' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('inventory_records_count')
                    ->label('Registros')
                    ->counts('inventoryRecords')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('context')
                    ->label('Contexto')
                    ->native(false)
                    ->searchable()
                    ->options(DocumentarySubseries::CONTEXTS),

                Tables\Filters\SelectFilter::make('documentary_series_id')
                    ->label('Serie Documental')
                    ->relationship('documentarySeries', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentarySubseries::route('/'),
            'create' => Pages\CreateDocumentarySubseries::route('/create'),
            'edit' => Pages\EditDocumentarySubseries::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
