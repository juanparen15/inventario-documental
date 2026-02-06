<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryRecordResource\Pages;
use App\Models\CcdEntry;
use App\Models\DocumentarySeries;
use App\Models\DocumentarySubseries;
use App\Models\InventoryRecord;
use App\Models\OrganizationalUnit;
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

    protected static ?string $navigationGroup = 'Inventario Documental';

    protected static ?string $modelLabel = 'Registro de Inventario';

    protected static ?string $pluralModelLabel = 'Registros de Inventario';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECCION 1: Identificacion del Registro
                Forms\Components\Section::make('Identificacion del Registro')
                    ->description('Formato Unico de Inventario Documental - FUID')
                    ->columns(2)
                    ->extraAttributes(['data-tour' => 'inv-oficina-productora'])
                    ->schema([
                        Forms\Components\Select::make('organizational_unit_id')
                            ->label('Oficina Productora')
                            ->options(function () {
                                $user = auth()->user();

                                // Si es super_admin, puede ver todas las unidades
                                if ($user?->hasRole('super_admin')) {
                                    return OrganizationalUnit::where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                }

                                // Si no, solo su unidad organizacional
                                if ($user?->organizational_unit_id) {
                                    return OrganizationalUnit::where('id', $user->organizational_unit_id)
                                        ->pluck('name', 'id');
                                }

                                return OrganizationalUnit::where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->default(fn() => auth()->user()?->organizational_unit_id)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $unit = OrganizationalUnit::find($state);
                                    $set('unit_code_display', $unit?->code ?? 'N/A');
                                }
                                $set('documentary_series_id', null);
                                $set('documentary_subseries_id', null);
                            }),

                        Forms\Components\TextInput::make('unit_code_display')
                            ->label('Codigo Oficina')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn() => auth()->user()?->organizationalUnit?->code ?? 'N/A')
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record?->organizational_unit_id) {
                                    $unit = OrganizationalUnit::find($record->organizational_unit_id);
                                    $component->state($unit?->code ?? 'N/A');
                                }
                            }),

                        Forms\Components\Select::make('inventory_purpose')
                            ->label('Objeto')
                            ->searchable()
                            ->native(false)
                            ->options(InventoryRecord::INVENTORY_PURPOSES)
                            ->required()
                            ->columnSpanFull()
                            ->extraAttributes(['data-tour' => 'inv-objeto'])
                            ->helperText('Seleccione el proposito del inventario documental'),
                    ]),

                // SECCION 2: Clasificacion Documental (FUID)
                Forms\Components\Section::make('Clasificacion Documental')
                    ->description('Series y Subseries del Inventario Documental (FUID)')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('documentary_series_id')
                            ->label('Serie Documental')
                            ->options(function (Get $get, $state) {
                                $unitId = $get('organizational_unit_id');
                                if (!$unitId) {
                                    return [];
                                }

                                $seriesIds = CcdEntry::where('organizational_unit_id', $unitId)
                                    ->distinct()
                                    ->pluck('documentary_series_id');

                                return DocumentarySeries::where(function ($query) use ($seriesIds) {
                                        $query->whereIn('id', $seriesIds)
                                            ->where('is_active', true)
                                            ->where('context', 'fuid');
                                    })
                                    ->when($state, fn($query) => $query->orWhere('id', $state))
                                    ->orderBy('code')
                                    ->get()
                                    ->mapWithKeys(fn($s) => [$s->id => "{$s->code} - {$s->name}"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('documentary_subseries_id', null))
                            ->required()
                            ->extraAttributes(['data-tour' => 'inv-serie']),

                        Forms\Components\Select::make('documentary_subseries_id')
                            ->label('Subserie Documental')
                            ->required(function (Get $get) {
                                $unitId = $get('organizational_unit_id');
                                $seriesId = $get('documentary_series_id');
                                if (!$unitId || !$seriesId) {
                                    return false;
                                }
                                return CcdEntry::where('organizational_unit_id', $unitId)
                                    ->where('documentary_series_id', $seriesId)
                                    ->whereNotNull('documentary_subseries_id')
                                    ->exists();
                            })
                            ->extraAttributes(['data-tour' => 'inv-subserie'])
                            ->options(function (Get $get, $state) {
                                $unitId = $get('organizational_unit_id');
                                $seriesId = $get('documentary_series_id');
                                if (!$unitId || !$seriesId) {
                                    return [];
                                }

                                $subseriesIds = CcdEntry::where('organizational_unit_id', $unitId)
                                    ->where('documentary_series_id', $seriesId)
                                    ->whereNotNull('documentary_subseries_id')
                                    ->pluck('documentary_subseries_id');

                                if ($subseriesIds->isEmpty() && !$state) {
                                    return [];
                                }

                                return DocumentarySubseries::where(function ($query) use ($subseriesIds) {
                                        $query->whereIn('id', $subseriesIds)
                                            ->where('is_active', true)
                                            ->where('context', 'fuid');
                                    })
                                    ->when($state, fn($query) => $query->orWhere('id', $state))
                                    ->orderBy('code')
                                    ->get()
                                    ->mapWithKeys(fn($s) => [$s->id => "{$s->code} - {$s->name}"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload(),
                    ]),

                // SECCION 3: Descripcion de la Unidad Documental
                Forms\Components\Section::make('Descripcion de la Unidad Documental')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Nombre de la Unidad Documental')
                            ->helperText('Nombre con el cual se identifica la unidad documental (opcional)')
                            ->maxLength(255)
                            ->extraInputAttributes(['data-tour' => 'inv-titulo'])
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripcion')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // SECCION 4: Fechas Extremas
                Forms\Components\Section::make('Fechas Extremas')
                    ->description('Fechas del documento principal (no anexos). Use "Sin Fecha" si no aplica.')
                    ->columns(4)
                    ->collapsible()
                    ->extraAttributes(['data-tour' => 'inv-fechas'])
                    ->schema([
                        Forms\Components\Toggle::make('has_start_date')
                            ->label('Tiene fecha inicial')
                            ->default(true)
                            ->live()
                            ->inline(false),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha Inicial')
                            ->displayFormat('Y-m-d')
                            ->required()
                            ->visible(fn(Get $get) => $get('has_start_date'))
                            ->helperText('Formato: DD-MM-AAAA'),

                        Forms\Components\Toggle::make('has_end_date')
                            ->label('Tiene fecha final')
                            ->default(true)
                            ->required()
                            ->live()
                            ->inline(false),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha Final')
                            ->displayFormat('Y-m-d')
                            ->visible(fn(Get $get) => $get('has_end_date'))
                            ->afterOrEqual('start_date')
                            ->helperText('Formato: DD-MM-AAAA'),

                        Forms\Components\Placeholder::make('sin_fecha_inicial')
                            ->label('Fecha Inicial')
                            ->content('S.F. (Sin Fecha)')
                            ->visible(fn(Get $get) => !$get('has_start_date')),

                        Forms\Components\Placeholder::make('sin_fecha_final')
                            ->label('Fecha Final')
                            ->content('S.F. (Sin Fecha)')
                            ->visible(fn(Get $get) => !$get('has_end_date')),
                    ]),

                // SECCION 5: Ubicacion Fisica
                Forms\Components\Section::make('Ubicacion Fisica')
                    ->columns(4)
                    ->collapsible()
                    ->extraAttributes(['data-tour' => 'inv-ubicacion'])
                    ->schema([
                        Forms\Components\TextInput::make('box')
                            ->label('No. Caja')
                            ->helperText('Numero consecutivo de caja')
                            ->required()
                            ->numeric()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('folder')
                            ->label('No. Carpeta')
                            ->helperText('Consecutivo iniciando en 1 por caja')
                            ->required()
                            ->numeric()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('volume')
                            ->label('No. Tomo/Legajo/Libro')
                            ->helperText('Si aplica material empastado')
                            ->required()
                            ->numeric()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('folios')
                            ->label('No. Folios')
                            ->helperText('Rango de folios en papel')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                    ]),

                // SECCION 6: Soporte
                Forms\Components\Section::make('Soporte')
                    ->columns(3)
                    ->collapsible()
                    ->extraAttributes(['data-tour' => 'inv-soporte'])
                    ->schema([
                        Forms\Components\Select::make('storage_medium_id')
                            ->label('Soporte Fisico/Electronico')
                            ->relationship('storageMedium', 'name', fn(Builder $query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->helperText('Papel, electronico o ambos'),

                        Forms\Components\Select::make('storage_unit_type')
                            ->label('Tipo Unidad de Almacenamiento')
                            ->options(InventoryRecord::STORAGE_UNIT_TYPES)
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->helperText('Microfilm, casette, CD, DVD, etc.'),

                        Forms\Components\TextInput::make('storage_unit_quantity')
                            ->label('Cantidad Unidades')
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn(Get $get) => !empty($get('storage_unit_type')))
                            ->helperText('Cantidad de unidades de almacenamiento'),
                    ]),

                // SECCION 7: Archivos Adjuntos
                Forms\Components\Section::make('Archivo Digitalizado')
                    ->extraAttributes(['data-tour' => 'inv-adjuntos'])
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Documentos PDF')
                            ->directory('inventory-records')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(20480) // 20MB
                            ->downloadable()
                            ->openable()
                            ->reorderable()
                            ->helperText('Solo archivos PDF. Maximo 20MB por archivo.')
                            ->columnSpanFull(),
                    ]),

                // SECCION 8: Informacion Adicional
                Forms\Components\Section::make('Informacion Adicional')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('priority_level_id')
                            ->label('Nivel de Prioridad')
                            ->relationship('priorityLevel', 'name', fn(Builder $query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload(),
                    ]),

                // SECCION 9: Notas
                Forms\Components\Section::make('Notas')
                    ->description('Informacion relevante no registrada en otros campos')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(4)
                            ->helperText('Registrar: faltantes, errores de numeracion, estado de conservacion (deterioro fisico, quimico, biologico), documentos relativos a DDHH y DIH, o NA (No Aplica)')
                            ->columnSpanFull(),
                    ]),

                // SECCION 10: Informacion del Sistema (solo en edicion)
                Forms\Components\Section::make('Informacion del Sistema')
                    ->schema([
                        Forms\Components\TextInput::make('reference_code')
                            ->label('Codigo de Referencia')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(5)
            ->deferLoading()
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Codigo')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Unidad Documental')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn($record) => $record->title),

                Tables\Columns\TextColumn::make('organizationalUnit.name')
                    ->label('Oficina Productora')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('inventory_purpose')
                    ->label('Objeto')
                    ->badge()
                    ->formatStateUsing(fn($state) => InventoryRecord::INVENTORY_PURPOSES[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('documentarySeries.name')
                    ->label('Serie')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('documentarySubseries.name')
                    ->label('Subserie')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('box')
                    ->label('Caja')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('folder')
                    ->label('Carpeta')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha Inicial')
                    ->formatStateUsing(fn($state, $record) => $record->has_start_date ? ($state?->format('Y-m-d') ?? '-') : 'S.F.')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha Final')
                    ->formatStateUsing(fn($state, $record) => $record->has_end_date ? ($state?->format('Y-m-d') ?? '-') : 'S.F.')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('folios')
                    ->label('Folios')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('attachments')
                    ->label('Archivos PDF')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '-';
                        }
                        $count = is_array($state) ? count($state) : 1;
                        return $count . ' PDF' . ($count > 1 ? 's' : '');
                    })
                    ->icon(fn ($state) => !empty($state) ? 'heroicon-o-document' : null)
                    ->color(fn ($state) => !empty($state) ? 'success' : 'gray')
                    ->action(
                        Tables\Actions\Action::make('viewAttachments')
                            ->modalHeading('Archivos PDF Adjuntos')
                            ->modalContent(function (InventoryRecord $record) {
                                $attachments = $record->attachments ?? [];
                                if (empty($attachments)) {
                                    return view('filament.components.no-attachments');
                                }
                                return view('filament.components.attachments-list', [
                                    'attachments' => $attachments,
                                ]);
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Cerrar')
                    )
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('organizational_unit_id')
                    ->label('Oficina Productora')
                    ->relationship('organizationalUnit', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('inventory_purpose')
                    ->label('Objeto')
                    ->options(InventoryRecord::INVENTORY_PURPOSES),

                Tables\Filters\SelectFilter::make('documentary_series_id')
                    ->label('Serie Documental')
                    ->relationship('documentarySeries', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('documentary_subseries_id')
                    ->label('Subserie Documental')
                    ->relationship('documentarySubseries', 'name')
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
                                fn(Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('storage_medium_id')
                    ->label('Soporte')
                    ->relationship('storageMedium', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        // Si no es super_admin, solo ver registros de su unidad organizacional
        $user = auth()->user();
        if ($user && !$user->hasRole('super_admin') && $user->organizational_unit_id) {
            $query->where('organizational_unit_id', $user->organizational_unit_id);
        }

        return $query;
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
