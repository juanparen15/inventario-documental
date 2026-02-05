<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ImportErrors extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string $view = 'filament.pages.import-errors';

    protected static ?string $title = 'Errores de Importación';

    protected static ?string $navigationLabel = 'Errores de Importación';

    protected static bool $shouldRegisterNavigation = false;

    public array $errors = [];

    public function mount(): void
    {
        $this->errors = session('import_errors', []);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
