<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Support\ActivityFormatter;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Registro de Actividad';

    protected static ?string $modelLabel = 'Actividad';

    protected static ?string $pluralModelLabel = 'Registro de Actividad';

    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    // -------------------------------------------------------------------------
    // Helpers de presentación (delegados a ActivityFormatter)
    // -------------------------------------------------------------------------

    public static function getSubjectLabel(?string $type): string
    {
        return match ($type) {
            'App\Models\AdministrativeAct'    => 'Sistema Unificado de Registro',
            'App\Models\InventoryRecord'      => 'Inventario Documental',
            'App\Models\OrganizationalUnit'   => 'Unidad Organizacional',
            'App\Models\DocumentarySeries'    => 'Serie Documental',
            'App\Models\DocumentarySubseries' => 'Subserie Documental',
            'App\Models\Entity'               => 'Entidad',
            'App\Models\ActClassification'    => 'Clasificación de Acto',
            'App\Models\StorageMedium'        => 'Medio de Almacenamiento',
            'App\Models\PriorityLevel'        => 'Nivel de Prioridad',
            'App\Models\User'                 => 'Usuario',
            default                           => class_basename($type ?? 'Desconocido'),
        };
    }

    public static function getEventConfig(string $event): array
    {
        return match ($event) {
            'created'  => ['label' => 'Creado',       'color' => 'success', 'icon' => 'heroicon-o-plus-circle'],
            'updated'  => ['label' => 'Editado',      'color' => 'info',    'icon' => 'heroicon-o-pencil-square'],
            'deleted'  => ['label' => 'Eliminado',    'color' => 'danger',  'icon' => 'heroicon-o-trash'],
            'restored' => ['label' => 'Restaurado',   'color' => 'warning', 'icon' => 'heroicon-o-arrow-path'],
            'viewed'   => ['label' => 'Consultado',   'color' => 'gray',    'icon' => 'heroicon-o-eye'],
            'uploaded' => ['label' => 'PDF subido',   'color' => 'warning', 'icon' => 'heroicon-o-arrow-up-tray'],
            default    => ['label' => ucfirst($event), 'color' => 'gray',   'icon' => 'heroicon-o-information-circle'],
        };
    }

    public static function getSubjectIdentifier(Activity $record): string
    {
        return ActivityFormatter::subjectIdentifier($record);
    }

    // -------------------------------------------------------------------------
    // Tabla
    // -------------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->defaultPaginationPageOption(15)
            ->columns([
                Tables\Columns\TextColumn::make('event')
                    ->label('Evento')
                    ->badge()
                    ->formatStateUsing(fn($state) => static::getEventConfig($state ?? '')['label'])
                    ->color(fn($state) => static::getEventConfig($state ?? '')['color'])
                    ->icon(fn($state) => static::getEventConfig($state ?? '')['icon'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Recurso')
                    ->formatStateUsing(fn($state) => static::getSubjectLabel($state))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('subject_identifier')
                    ->label('Registro')
                    ->getStateUsing(fn(Activity $record) => static::getSubjectIdentifier($record))
                    ->searchable(false),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('causer_unit')
                    ->label('Unidad')
                    ->getStateUsing(function (Activity $record): string {
                        try {
                            return $record->causer?->organizationalUnit?->name ?? '—';
                        } catch (\Throwable) {
                            return '—';
                        }
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripcion')
                    ->limit(55)
                    ->tooltip(fn($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('properties_summary')
                    ->label('Campos cambiados')
                    ->getStateUsing(fn(Activity $record) => ActivityFormatter::changedFieldsSummary($record))
                    ->limit(60)
                    ->tooltip(fn(Activity $record) => ActivityFormatter::changedFieldsSummary($record))
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha y Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->icon('heroicon-o-clock'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Evento')
                    ->options([
                        'created'  => 'Creado',
                        'updated'  => 'Editado',
                        'deleted'  => 'Eliminado',
                        'restored' => 'Restaurado',
                        'viewed'   => 'Consultado',
                        'uploaded' => 'PDF subido',
                    ]),

                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Recurso')
                    ->options([
                        'App\Models\AdministrativeAct'    => 'Sistema Unificado de Registro',
                        'App\Models\InventoryRecord'      => 'Inventario Documental',
                        'App\Models\OrganizationalUnit'   => 'Unidad Organizacional',
                        'App\Models\DocumentarySeries'    => 'Serie Documental',
                        'App\Models\DocumentarySubseries' => 'Subserie Documental',
                        'App\Models\Entity'               => 'Entidad',
                        'App\Models\ActClassification'    => 'Clasificación de Acto',
                        'App\Models\User'                 => 'Usuario',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Desde'),
                        Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'], fn(Builder $q, $d) => $q->whereDate('created_at', '<=', $d));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Desde ' . $data['from'])->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Hasta ' . $data['until'])->removeField('until');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('ver_detalle')
                    ->label('Detalle')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('gray')
                    ->modalHeading('Detalle de actividad')
                    ->modalContent(fn(Activity $record) => view(
                        'filament.components.activity-detail',
                        [
                            'activity'     => $record,
                            'eventConfig'  => static::getEventConfig($record->event ?? ''),
                            'subjectLabel' => static::getSubjectLabel($record->subject_type),
                            'identifier'   => static::getSubjectIdentifier($record),
                        ]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
