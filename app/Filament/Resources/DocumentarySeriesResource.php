<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentarySeriesResource\Pages;
use App\Models\CcdEntry;
use App\Models\DocumentarySeries;
use App\Models\OrganizationalUnit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentarySeriesResource extends Resource
{
    protected static ?string $model = DocumentarySeries::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Tabla de Retencion Documental';

    protected static ?string $modelLabel = 'Serie Documental';

    protected static ?string $pluralModelLabel = 'Series Documentales';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
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

                        Forms\Components\Select::make('context')
                            ->label('Contexto')
                            ->options(DocumentarySeries::CONTEXTS)
                            ->required()
                            ->default('ccd')
                            ->helperText('FUID: Series del Inventario Documental. CCD: Series del Cuadro de Clasificacion Documental.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Unidades Organizacionales')
                    ->description('Seleccione las unidades que tendran acceso a esta serie')
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
                                    $unitIds = CcdEntry::where('documentary_series_id', $record->id)
                                        ->distinct()
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

                Tables\Columns\TextColumn::make('context')
                    ->label('Contexto')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => DocumentarySeries::CONTEXTS[$state] ?? $state)
                    ->color(fn(string $state): string => match ($state) {
                        'fuid' => 'info',
                        'ccd' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('documentary_subseries_count')
                    ->label('Subseries')
                    ->counts('documentarySubseries')
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
                    ->options(DocumentarySeries::CONTEXTS),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->native(false)
                    ->searchable()
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
            'index' => Pages\ListDocumentarySeries::route('/'),
            'create' => Pages\CreateDocumentarySeries::route('/create'),
            'edit' => Pages\EditDocumentarySeries::route('/{record}/edit'),
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
