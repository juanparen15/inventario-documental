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

    protected static ?string $navigationGroup = 'Documentos';

    protected static ?string $navigationLabel = 'Sistema unificado de registro';

    protected static ?string $modelLabel = 'Sistema unificado de registro';

    protected static ?string $pluralModelLabel = 'Sistema unificado de registro';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informacion del Sistema Unificado de Registro')
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

                        Forms\Components\Placeholder::make('entity_display')
                            ->label('Entidad')
                            ->content(function (Get $get, ?AdministrativeAct $record) {
                                $unitId = $get('organizational_unit_id') ?? $record?->organizational_unit_id;
                                if (! $unitId) return '—';
                                return OrganizationalUnit::with('entity')->find($unitId)?->entity?->name ?? '—';
                            }),

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
                            ->disabled(fn(?AdministrativeAct $record) => $record !== null)
                            ->dehydrated()
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
                            ->disabled(fn(?AdministrativeAct $record) => $record !== null)
                            ->dehydrated()
                            ->required(function (Get $get, ?AdministrativeAct $record) {
                                if ($record !== null) {
                                    return false;
                                }
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
                            ->maxSize(204800)
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
                                $largeFiles = 0;
                                foreach ($files as $file) {
                                    try {
                                        $path = $file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                                            ? $file->getRealPath()
                                            : storage_path('app/public/' . $file);

                                        if ($path && file_exists($path)) {
                                            if (@filesize($path) > 10 * 1024 * 1024) {
                                                $largeFiles++;
                                            } else {
                                                $totalPages += static::countPagesFromPdf($path);
                                            }
                                        }
                                    } catch (\Throwable $e) {
                                        // Never block the form
                                    }
                                }

                                if ($totalPages > 0 && $largeFiles === 0) {
                                    return "{$totalPages} folios";
                                }

                                if ($largeFiles > 0) {
                                    $msg = $totalPages > 0 ? "{$totalPages} folios + " : '';
                                    return "{$msg}{$largeFiles} archivo(s) grande(s) — folios no contabilizados automáticamente";
                                }

                                return count($files) . ' archivo(s) adjunto(s)';
                            })
                            ->extraAttributes(['data-tour' => 'act-folios'])
                            ->helperText('Calculado automaticamente a partir de los PDF adjuntos.'),
                    ]),

                Forms\Components\Section::make('Documentos Confidenciales')
                    ->description('Solo tú, usuarios de tu unidad y el administrador pueden ver estos documentos. Los supervisores no tienen acceso.')
                    ->icon('heroicon-o-lock-closed')
                    ->iconColor('warning')
                    ->collapsible()
                    ->schema([
                        Forms\Components\FileUpload::make('confidential_attachments')
                            ->label('Archivos Confidenciales (PDF)')
                            ->directory('administrative-acts-confidential')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(204800)
                            ->downloadable()
                            ->openable()
                            ->reorderable()
                            ->live(),
                    ])
                    ->hidden(fn() => auth()->user()?->hasRole('supervisor'))
                    ->dehydrated(fn() => !auth()->user()?->hasRole('supervisor')),

                Forms\Components\Section::make('Registro de retraso')
                    ->icon('heroicon-o-clock')
                    ->iconColor('danger')
                    ->schema([
                        Forms\Components\Placeholder::make('late_upload_reason')
                            ->label('Razón del retraso')
                            ->content(fn(?AdministrativeAct $record) => $record?->late_upload_reason ?? '—'),
                    ])
                    ->hidden(fn(?AdministrativeAct $record) => empty($record?->late_upload_reason)),
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
                    ->copyable()
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
                    ->copyable()
                    ->limit(50)
                    ->tooltip(fn($record) => $record->subject),

                Tables\Columns\TextColumn::make('folios')
                    ->label('Folios')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Tables\Columns\TextColumn::make('attachments')
                //     ->label('Archivos PDF')
                //     ->sortable()
                //     ->formatStateUsing(function ($state) {
                //         if (empty($state)) {
                //             return '—';
                //         }
                //         $count = is_array($state) ? count($state) : 1;
                //         return $count . ' PDF' . ($count > 1 ? 's' : '');
                //     })
                //     ->icon(fn($state) => !empty($state) ? 'heroicon-o-document' : null)
                //     ->color(fn($state) => !empty($state) ? 'success' : 'gray')
                //     ->toggleable(),

                Tables\Columns\TextColumn::make('confidential_attachments')
                    ->label('Conf.')
                    ->getStateUsing(function (AdministrativeAct $record): string {
                        $count = count($record->confidential_attachments ?? []);
                        return $count > 0 ? "🔒 {$count}" : '—';
                    })
                    ->color(fn($state) => $state !== '—' ? 'warning' : 'gray')
                    ->tooltip('Archivos confidenciales')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('organizationalUnit.entity.name')
                    ->label('Entidad')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn() => auth()->user()?->hasAnyRole(['super_admin', 'supervisor'])),

                Tables\Columns\TextColumn::make('organizationalUnit.name')
                    ->label('Unidad')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn() => auth()->user()?->hasAnyRole(['super_admin', 'supervisor'])),

                Tables\Columns\TextColumn::make('pdf_days_remaining')
                    ->label('Días para PDF')
                    ->getStateUsing(function (AdministrativeAct $record): string {
                        if (! $record->lacksPdf()) {
                            return '✓';
                        }
                        $days = $record->pdfDaysRemaining();
                        if ($days < 0) {
                            return 'Vencido (' . abs($days) . 'd)';
                        }
                        return $days . ' días';
                    })
                    ->badge()
                    ->color(function (AdministrativeAct $record): string {
                        if (! $record->lacksPdf()) {
                            return 'success';
                        }
                        $days = $record->pdfDaysRemaining();
                        if ($days < 0) return 'danger';
                        if ($days <= 5) return 'danger';
                        if ($days <= 15) return 'warning';
                        return 'info';
                    })
                    ->sortable(false)
                    ->toggleable(),

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
                    ->preload()
                    ->visible(fn() => auth()->user()?->hasAnyRole(['super_admin', 'supervisor'])),

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

                Tables\Actions\Action::make('viewAttachments')
                    ->label('Ver PDFs')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->authorize(fn() => true)
                    ->visible(
                        fn(AdministrativeAct $record) =>
                        !empty($record->attachments) || !empty($record->confidential_attachments)
                    )
                    ->modalHeading('Archivos PDF Adjuntos')
                    ->modalWidth('5xl')
                    ->modalContent(function (AdministrativeAct $record) {
                        $attachments      = $record->attachments ?? [];
                        $isSuperAdmin     = auth()->user()?->hasRole('super_admin');
                        $isSupervisor     = auth()->user()?->hasRole('supervisor');
                        $confidentialAll  = $record->confidential_attachments ?? [];
                        $confidentialCount = count($confidentialAll);

                        // Supervisor no puede ver confidenciales
                        $confidential = $isSupervisor ? [] : $confidentialAll;

                        // Registrar actividad cuando super_admin consulta archivos confidenciales
                        if ($isSuperAdmin && $confidentialCount > 0) {
                            activity('inventory')
                                ->performedOn($record)
                                ->causedBy(auth()->user())
                                ->event('confidential_viewed')
                                ->withProperties([
                                    'ip'                    => request()->ip(),
                                    'archivos_confidenciales' => $confidentialCount,
                                ])
                                ->log('Super admin consultó archivos confidenciales');
                        }

                        if (empty($attachments) && empty($confidential) && (!$isSupervisor || $confidentialCount === 0)) {
                            return view('filament.components.no-attachments');
                        }
                        return view('filament.components.attachments-list', [
                            'attachments'       => $attachments,
                            'confidential'      => $confidential,
                            'confidentialCount' => $isSupervisor ? $confidentialCount : 0,
                            'isAdmin'           => $isSupervisor,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                Tables\Actions\EditAction::make()
                    ->visible(fn(AdministrativeAct $record) =>
                        auth()->user()?->hasRole('super_admin') ||
                        ($record->lacksPdf() && $record->created_at->diffInDays(now()) <= 30)
                    ),

                Tables\Actions\Action::make('uploadLate')
                    ->label('Subir PDF')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->modalHeading('Subir documento con retraso')
                    ->modalDescription('El plazo de 30 días ha vencido. Adjunta el documento PDF e indica la razón del retraso.')
                    ->modalWidth('lg')
                    ->visible(fn(AdministrativeAct $record) => $record->lacksPdf() && $record->pdfDaysRemaining() < 0)
                    ->form([
                        Forms\Components\Toggle::make('is_confidential')
                            ->label('Documento confidencial')
                            ->helperText('Activa esta opción si el documento debe ser privado para tu unidad.')
                            ->default(false)
                            ->live()
                            ->hidden(fn() => auth()->user()?->hasRole('supervisor')),

                        Forms\Components\FileUpload::make('regular_files')
                            ->label('Documentos PDF')
                            ->directory('administrative-acts')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(204800)
                            ->hidden(fn(Get $get) => $get('is_confidential') && !auth()->user()?->hasRole('supervisor'))
                            ->required(fn(Get $get) => !$get('is_confidential') || auth()->user()?->hasRole('supervisor')),

                        Forms\Components\FileUpload::make('confidential_files')
                            ->label('Documentos PDF (Confidencial 🔒)')
                            ->directory('administrative-acts-confidential')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(204800)
                            ->hidden(fn(Get $get) => !$get('is_confidential') || auth()->user()?->hasRole('supervisor'))
                            ->required(fn(Get $get) => $get('is_confidential') && !auth()->user()?->hasRole('supervisor')),

                        Forms\Components\Textarea::make('late_upload_reason')
                            ->label('Razón del retraso')
                            ->placeholder('Explique por qué no se subió el documento dentro del plazo de 30 días.')
                            ->required()
                            ->maxLength(1000)
                            ->rows(3),
                    ])
                    ->action(function (AdministrativeAct $record, array $data): void {
                        $isConfidential = ($data['is_confidential'] ?? false)
                            && !auth()->user()?->hasRole('supervisor');

                        if ($isConfidential) {
                            $files = array_values(array_filter($data['confidential_files'] ?? []));
                            $record->update([
                                'confidential_attachments' => $files,
                                'late_upload_reason'       => $data['late_upload_reason'],
                            ]);
                        } else {
                            $files = array_values(array_filter($data['regular_files'] ?? []));
                            $folios = 0;
                            foreach ($files as $file) {
                                try {
                                    $path = storage_path('app/public/' . $file);
                                    if (file_exists($path)) {
                                        $folios += static::countPagesFromPdf($path);
                                    }
                                } catch (\Throwable) {
                                }
                            }
                            $record->update([
                                'attachments'        => $files,
                                'late_upload_reason' => $data['late_upload_reason'],
                                'folios'             => $folios > 0 ? $folios : null,
                            ]);
                        }

                        activity('inventory')
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->event('uploaded')
                            ->withProperties([
                                'ip'             => request()->ip(),
                                'confidencial'   => $isConfidential,
                                'archivos'       => count($files),
                                'razon_retraso'  => $data['late_upload_reason'],
                            ])
                            ->log('PDF subido con retraso');
                    })
                    ->successNotificationTitle('Documento subido correctamente'),

                Tables\Actions\DeleteAction::make(),
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
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        $user = auth()->user();

        if ($user && ! $user->hasAnyRole(['super_admin', 'supervisor'])) {
            if ($user->organizational_unit_id) {
                $query->where('organizational_unit_id', $user->organizational_unit_id);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        return $query;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['filing_number', 'subject'];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['super_admin', 'supervisor'])) {
            if ($user->organizational_unit_id) {
                return static::getModel()::where('organizational_unit_id', $user->organizational_unit_id)->count();
            }
            return '0';
        }
        return static::getModel()::count();
    }

    /**
     * Cuenta las paginas de un PDF de forma robusta.
     * Intenta con smalot/pdfparser primero, luego con regex como fallback.
     */
    public static function countPagesFromPdf(string $path): int
    {
        // Omitir archivos grandes para evitar OOM en producción (>10 MB)
        if (@filesize($path) > 10 * 1024 * 1024) {
            return 0;
        }

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
