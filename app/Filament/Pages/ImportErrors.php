<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ImportErrors extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string $view = 'filament.pages.import-errors';

    protected static ?string $title = 'Resultado de Importación';

    protected static ?string $navigationLabel = 'Resultado de Importación';

    protected static bool $shouldRegisterNavigation = false;

    public array $errors = [];

    public array $created = [];

    public function mount(): void
    {
        $this->errors = session('import_errors', []);
        $this->created = session('import_created', []);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasCreated(): bool
    {
        return !empty($this->created);
    }
}
