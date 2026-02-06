<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdministrativeActResource\Pages;
use App\Models\AdministrativeAct;
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

class AdministrativeActResource extends Resource
{
    protected static ?string $model = AdministrativeAct::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $modelLabel = 'Acto Administrativo';

    protected static ?string $pluralModelLabel = 'Actos Administrativos';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informacion del Acto')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('organizational_unit_id')
                            ->label('Unidad Organizacional')
                            ->options(function () {
                                $user = auth()->user();
                                if ($user?->hasRole('super_admin')) {
                                    return OrganizationalUnit::where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                }
                                if ($user?->organizational_unit_id) {
                                    return OrganizationalUnit::where('id', $user->organizational_unit_id)
                                        ->pluck('name', 'id');
                                }
                                return [];
                            })
                            ->default(fn() => auth()->user()?->organizational_unit_id)
                            ->disabled(fn() => !auth()->user()?->hasRole('super_admin'))
                            ->dehydrated()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('documentary_series_id', null);
                                $set('documentary_subseries_id', null);
                                $set('filing_number_preview', null);
                            })
                            ->extraAttributes(['data-tour' => 'act-unidad']),

                        Forms\Components\Placeholder::make('vigencia_display')
                            ->label('Vigencia')
                            ->content(fn(?AdministrativeAct $record) => $record?->vigencia ?? date('Y'))
                            ->extraAttributes(['data-tour' => 'act-vigencia']),

                        Forms\Components\Hidden::make('vigencia')
                            ->default(date('Y'))
                            ->extraAttributes(['data-tour' => 'act-vigencia'])
                            ->dehydrated(true),

                        Forms\Components\Placeholder::make('filing_number_preview')
                            ->label('Consecutivo (automatico)')
                            ->content(function (Get $get, ?AdministrativeAct $record) {
                                if ($record?->filing_number) {
                                    return $record->filing_number;
                                }
                                $preview = AdministrativeAct::previewFilingNumber(
                                    $get('vigencia') ? (int) $get('vigencia') : null,
                                    $get('organizational_unit_id'),
                                    $get('documentary_series_id'),
                                    $get('documentary_subseries_id'),
                                );
                                return $preview ?? 'Seleccione unidad y serie para generar';
                            })
                            ->extraAttributes(['data-tour' => 'act-consecutivo']),

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
                                        ->where('context', 'ccd');
                                })
                                    ->when($state, fn($query) => $query->orWhere('id', $state))
                                    ->orderBy('code')
                                    ->get()
                                    ->mapWithKeys(fn($s) => [$s->id => "{$s->code} - {$s->name}"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('documentary_subseries_id', null);
                                $set('filing_number_preview', null);
                            })
                            ->extraAttributes(['data-tour' => 'act-serie']),

                        Forms\Components\Select::make('documentary_subseries_id')
                            ->label('Subserie Documental')
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
                                        ->where('context', 'ccd');
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
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('filing_number_preview', null))
                            ->extraAttributes(['data-tour' => 'act-subserie']),

                        Forms\Components\TextInput::make('subject')
                            ->label('Objeto / Asunto')
                            ->required()
                            ->maxLength(1000)
                            ->extraInputAttributes(['data-tour' => 'act-asunto'])
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->extraAttributes(['data-tour' => 'act-notas'])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Archivos Adjuntos')
                    ->extraAttributes(['data-tour' => 'act-adjuntos'])
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Documentos PDF')
                            ->directory('administrative-acts')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(20480)
                            ->downloadable()
                            ->openable()
                            ->reorderable()
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('folios_display')
                            ->label('Folios (paginas PDF)')
                            ->content(function (Get $get) {
                                $files = array_filter($get('attachments') ?? []);
                                if (empty($files)) {
                                    return 'Sin archivos';
                                }

                                $totalPages = 0;
                                foreach ($files as $file) {
                                    try {
                                        $path = $file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                                            ? $file->getRealPath()
                                            : storage_path('app/public/' . $file);

                                        if ($path && file_exists($path)) {
                                            $totalPages += static::countPagesFromPdf($path);
                                        }
                                    } catch (\Throwable $e) {
                                        // Never block the form
                                    }
                                }

                                if ($totalPages > 0) {
                                    return "{$totalPages} folios";
                                }

                                return count($files) . ' archivo(s) adjunto(s)';
                            })
                            ->extraAttributes(['data-tour' => 'act-folios'])
                            ->helperText('Calculado automaticamente a partir de los PDF adjuntos.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(5)
            ->deferLoading()
            ->columns([
                Tables\Columns\TextColumn::make('vigencia')
                    ->label('Vigencia')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('filing_number')
                    ->label('Consecutivo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('documentarySeries.name')
                    ->label('Serie')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('documentarySubseries.name')
                    ->label('Subserie')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Objeto')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn($record) => $record->subject),

                Tables\Columns\TextColumn::make('folios')
                    ->label('Folios')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('attachments')
                    ->label('Archivos PDF')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '-';
                        }
                        $count = is_array($state) ? count($state) : 1;
                        return $count . ' PDF' . ($count > 1 ? 's' : '');
                    })
                    ->icon(fn($state) => !empty($state) ? 'heroicon-o-document' : null)
                    ->color(fn($state) => !empty($state) ? 'success' : 'gray')
                    ->action(
                        Tables\Actions\Action::make('viewAttachments')
                            ->modalHeading('Archivos PDF Adjuntos')
                            ->modalContent(function (AdministrativeAct $record) {
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
                    ->toggleable(),

                Tables\Columns\TextColumn::make('organizationalUnit.name')
                    ->label('Unidad')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn() => auth()->user()?->hasRole('super_admin')),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vigencia')
                    ->label('Vigencia')
                    ->options(function () {
                        return AdministrativeAct::query()
                            ->select('vigencia')
                            ->distinct()
                            ->orderByDesc('vigencia')
                            ->pluck('vigencia', 'vigencia')
                            ->toArray();
                    })
                    ->searchable(),

                Tables\Filters\SelectFilter::make('organizational_unit_id')
                    ->label('Unidad Organizacional')
                    ->relationship('organizationalUnit', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('documentary_series_id')
                    ->label('Serie Documental')
                    ->relationship('documentarySeries', 'name', fn(Builder $query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn(Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdministrativeActs::route('/'),
            'create' => Pages\CreateAdministrativeAct::route('/create'),
            'view' => Pages\ViewAdministrativeAct::route('/{record}'),
            'edit' => Pages\EditAdministrativeAct::route('/{record}/edit'),
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
        return ['filing_number', 'subject'];
    }

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->hasRole('super_admin')) {
            return static::getModel()::count();
        } elseif (auth()->user()?->organizational_unit_id) {
            return static::getModel()::where('organizational_unit_id', auth()->user()->organizational_unit_id)->count();
        }
        return null;
    }

    /**
     * Cuenta las paginas de un PDF de forma robusta.
     * Intenta con smalot/pdfparser primero, luego con regex como fallback.
     */
    public static function countPagesFromPdf(string $path): int
    {
        // Intento 1: smalot/pdfparser (mas preciso)
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);
            $count = count($pdf->getPages());
            if ($count > 0) {
                return $count;
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        // Intento 2: contar marcadores /Type /Page en el contenido crudo del PDF
        try {
            $content = file_get_contents($path);
            if ($content !== false) {
                $count = preg_match_all('/\/Type\s*\/Page(?!s)/', $content);
                if ($count > 0) {
                    return $count;
                }
            }
        } catch (\Throwable $e) {
            // No se pudo leer
        }

        return 0;
    }
}
