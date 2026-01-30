<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryRecordResource\Pages;
use App\Models\DocumentaryClass;
use App\Models\DocumentarySubseries;
use App\Models\DocumentType;
use App\Models\InventoryRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryRecordResource extends Resource
{
    protected static ?string $model = InventoryRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $modelLabel = 'Registro de Inventario';

    protected static ?string $pluralModelLabel = 'Registros de Inventario';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Clasificación Documental')
                    ->description('Seleccione la clasificación del documento')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('organizational_unit_id')
                            ->label('Unidad Organizacional')
                            ->relationship('organizationalUnit', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('documentary_series_id')
                            ->label('Serie Documental')
                            ->relationship('documentarySeries', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('documentary_subseries_id', null);
                                $set('documentary_class_id', null);
                                $set('document_type_id', null);
                            })
                            ->required(),

                        Forms\Components\Select::make('documentary_subseries_id')
                            ->label('Subserie Documental')
                            ->options(function (Get $get) {
                                $seriesId = $get('documentary_series_id');
                                if (!$seriesId) {
                                    return [];
                                }
                                return DocumentarySubseries::where('documentary_series_id', $seriesId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('documentary_class_id', null);
                                $set('document_type_id', null);
                            }),

                        Forms\Components\Select::make('documentary_class_id')
                            ->label('Clase Documental')
                            ->options(function (Get $get) {
                                $subseriesId = $get('documentary_subseries_id');
                                if (!$subseriesId) {
                                    return [];
                                }
                                return DocumentaryClass::where('documentary_subseries_id', $subseriesId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('document_type_id', null)),

                        Forms\Components\Select::make('document_type_id')
                            ->label('Tipo de Documento')
                            ->options(function (Get $get) {
                                $classId = $get('documentary_class_id');
                                if (!$classId) {
                                    return [];
                                }
                                return DocumentType::where('documentary_class_id', $classId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload(),
                    ]),

                Forms\Components\Section::make('Información del Documento')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha Inicial')
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha Final')
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('start_date'),
                    ]),

                Forms\Components\Section::make('Ubicación Física')
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('box')
                            ->label('Caja')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('folder')
                            ->label('Carpeta')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('volume')
                            ->label('Tomo')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('folios')
                            ->label('Folios')
                            ->numeric()
                            ->minValue(0),
                    ]),

                Forms\Components\Section::make('Archivos Adjuntos')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Documentos Digitalizados')
                            ->directory('inventory-records')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/gif'])
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Catálogos')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('storage_medium_id')
                            ->label('Soporte')
                            ->relationship('storageMedium', 'name', fn (Builder $query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('document_purpose_id')
                            ->label('Objeto')
                            ->relationship('documentPurpose', 'name', fn (Builder $query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('process_type_id')
                            ->label('Tipo de Proceso')
                            ->relationship('processType', 'name', fn (Builder $query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('validity_status_id')
                            ->label('Estado de Vigencia')
                            ->relationship('validityStatus', 'name', fn (Builder $query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('priority_level_id')
                            ->label('Nivel de Prioridad')
                            ->relationship('priorityLevel', 'name', fn (Builder $query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('project_id')
                            ->label('Proyecto')
                            ->relationship('project', 'name', fn (Builder $query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload(),
                    ]),

                Forms\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas Adicionales')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Información del Sistema')
                    ->schema([
                        Forms\Components\TextInput::make('reference_code')
                            ->label('Código de Referencia')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('organizationalUnit.name')
                    ->label('Unidad')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('documentarySeries.name')
                    ->label('Serie')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('box')
                    ->label('Caja')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('folder')
                    ->label('Carpeta')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha Inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha Fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('folios')
                    ->label('Folios')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('organizational_unit_id')
                    ->label('Unidad Organizacional')
                    ->relationship('organizationalUnit', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('documentary_series_id')
                    ->label('Serie Documental')
                    ->relationship('documentarySeries', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('storage_medium_id')
                    ->label('Soporte')
                    ->relationship('storageMedium', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Proyecto')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (InventoryRecord $record) {
                        $newRecord = $record->replicate();
                        $newRecord->reference_code = null;
                        $newRecord->title = $record->title . ' (Copia)';
                        $newRecord->save();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Duplicar Registro')
                    ->modalDescription('Se creará una copia de este registro. El código de referencia será generado automáticamente.'),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListInventoryRecords::route('/'),
            'create' => Pages\CreateInventoryRecord::route('/create'),
            'view' => Pages\ViewInventoryRecord::route('/{record}'),
            'edit' => Pages\EditInventoryRecord::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'reference_code', 'box', 'folder', 'description'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
