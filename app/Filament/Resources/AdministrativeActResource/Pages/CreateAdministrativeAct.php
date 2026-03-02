<?php

namespace App\Filament\Resources\AdministrativeActResource\Pages;

use App\Filament\Resources\AdministrativeActResource;
use App\Models\AdministrativeAct;
use App\Models\CcdEntry;
use App\Models\DocumentarySeries;
use App\Models\DocumentarySubseries;
use App\Models\OrganizationalUnit;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;

class CreateAdministrativeAct extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = AdministrativeActResource::class;

    protected function getSteps(): array
    {
        return [

            // ─── Paso 1: Dependencia y Clasificación ─────────────────────────
            Step::make('Clasificación')
                ->description('Dependencia, serie y subserie documental')
                ->icon('heroicon-o-building-office-2')
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
                        ->default(fn () => auth()->user()?->organizational_unit_id)
                        ->disabled(fn () => ! auth()->user()?->hasRole('super_admin'))
                        ->dehydrated()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set) {
                            $set('documentary_series_id', null);
                            $set('documentary_subseries_id', null);
                        })
                        ->extraAttributes(['data-tour' => 'act-unidad']),

                    Forms\Components\Placeholder::make('vigencia_display')
                        ->label('Vigencia')
                        ->content(fn () => date('Y'))
                        ->extraAttributes(['data-tour' => 'act-vigencia']),

                    Forms\Components\Hidden::make('vigencia')
                        ->default(date('Y'))
                        ->dehydrated(true),

                    Forms\Components\Select::make('documentary_series_id')
                        ->label('Serie Documental')
                        ->options(function (Get $get, $state) {
                            $unitId = $get('organizational_unit_id');
                            if (! $unitId) {
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
                                ->when($state, fn ($q) => $q->orWhere('id', $state))
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn ($s) => [$s->id => "{$s->code} - {$s->name}"])
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set) {
                            $set('documentary_subseries_id', null);
                        })
                        ->extraAttributes(['data-tour' => 'act-serie']),

                    Forms\Components\Select::make('documentary_subseries_id')
                        ->label('Subserie Documental')
                        ->options(function (Get $get, $state) {
                            $unitId   = $get('organizational_unit_id');
                            $seriesId = $get('documentary_series_id');
                            if (! $unitId || ! $seriesId) {
                                return [];
                            }
                            $subseriesIds = CcdEntry::where('organizational_unit_id', $unitId)
                                ->where('documentary_series_id', $seriesId)
                                ->whereNotNull('documentary_subseries_id')
                                ->pluck('documentary_subseries_id');

                            if ($subseriesIds->isEmpty() && ! $state) {
                                return [];
                            }

                            return DocumentarySubseries::where(function ($query) use ($subseriesIds) {
                                $query->whereIn('id', $subseriesIds)
                                    ->where('is_active', true)
                                    ->where('context', 'ccd');
                            })
                                ->when($state, fn ($q) => $q->orWhere('id', $state))
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn ($s) => [$s->id => "{$s->code} - {$s->name}"])
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(function (Get $get) {
                            $unitId   = $get('organizational_unit_id');
                            $seriesId = $get('documentary_series_id');
                            if (! $unitId || ! $seriesId) {
                                return false;
                            }
                            return CcdEntry::where('organizational_unit_id', $unitId)
                                ->where('documentary_series_id', $seriesId)
                                ->whereNotNull('documentary_subseries_id')
                                ->exists();
                        })
                        ->extraAttributes(['data-tour' => 'act-subserie']),

                    Forms\Components\Placeholder::make('filing_number_preview')
                        ->label('Consecutivo (automático)')
                        ->content(function (Get $get) {
                            $preview = AdministrativeAct::previewFilingNumber(
                                $get('vigencia') ? (int) $get('vigencia') : null,
                                $get('organizational_unit_id'),
                                $get('documentary_series_id'),
                                $get('documentary_subseries_id'),
                            );
                            return $preview ?? 'Seleccione unidad y serie para generar';
                        })
                        ->extraAttributes(['data-tour' => 'act-consecutivo'])
                        ->columnSpanFull(),
                ]),

            // ─── Paso 2: Detalle del acto ─────────────────────────────────────
            Step::make('Detalle')
                ->description('Objeto y notas del acto administrativo')
                ->icon('heroicon-o-document-text')
                ->schema([

                    Forms\Components\TextInput::make('subject')
                        ->label('Objeto / Asunto')
                        ->required()
                        ->maxLength(1000)
                        ->extraAttributes(['data-tour' => 'act-asunto'])
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->rows(4)
                        ->extraAttributes(['data-tour' => 'act-notas'])
                        ->columnSpanFull(),
                ]),

            // ─── Paso 3: Documentos adjuntos ─────────────────────────────────
            Step::make('Documentos')
                ->description('Archivos PDF adjuntos (opcional)')
                ->icon('heroicon-o-paper-clip')
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
                        ->extraAttributes(['data-tour' => 'act-adjuntos'])
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('folios_display')
                        ->label('Folios (páginas PDF)')
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
                                        $totalPages += AdministrativeActResource::countPagesFromPdf($path);
                                    }
                                } catch (\Throwable $e) {
                                    //
                                }
                            }
                            if ($totalPages > 0) {
                                return "{$totalPages} folios";
                            }
                            return count($files) . ' archivo(s) adjunto(s)';
                        })
                        ->helperText('Calculado automáticamente a partir de los PDF adjuntos.')
                        ->extraAttributes(['data-tour' => 'act-folios'])
                        ->columnSpanFull(),

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
                                ->maxSize(20480)
                                ->downloadable()
                                ->openable()
                                ->reorderable()
                                ->live()
                                ->columnSpanFull(),
                        ])
                        ->hidden(fn () => auth()->user()?->hasRole('supervisor'))
                        ->dehydrated(fn () => ! auth()->user()?->hasRole('supervisor'))
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['folios'] = $this->countPdfPages($data['attachments'] ?? []);

        return $data;
    }

    protected function countPdfPages(array $files): ?int
    {
        $files = array_filter($files);
        if (empty($files)) {
            return null;
        }

        $totalPages = 0;
        foreach ($files as $file) {
            try {
                $path = storage_path('app/public/' . $file);
                if ($path && file_exists($path)) {
                    $totalPages += AdministrativeActResource::countPagesFromPdf($path);
                }
            } catch (\Throwable $e) {
                //
            }
        }

        return $totalPages > 0 ? $totalPages : null;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
