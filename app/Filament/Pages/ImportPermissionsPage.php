<?php

namespace App\Filament\Pages;

use App\Models\OrganizationalUnit;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ImportPermissionsPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Permisos de Importación';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $title           = 'Permisos de Importación';
    protected static ?int    $navigationSort  = 5;
    protected static string  $view            = 'filament.pages.import-permissions';

    public array $units = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }


    public function mount(): void
    {
        $this->loadUnits();
    }

    public function toggleImport(int $unitId): void
    {
        $unit = OrganizationalUnit::find($unitId);

        if (! $unit) {
            return;
        }

        $unit->update(['can_import' => ! $unit->can_import]);

        $this->loadUnits();

        Notification::make()
            ->title($unit->can_import ? 'Permiso activado' : 'Permiso desactivado')
            ->body($unit->can_import
                ? "{$unit->name} ahora puede importar registros."
                : "{$unit->name} ya no puede importar registros.")
            ->success()
            ->send();
    }

    protected function loadUnits(): void
    {
        $this->units = OrganizationalUnit::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'can_import'])
            ->toArray();
    }
}
