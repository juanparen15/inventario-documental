<?php

namespace App\Filament\Resources\OrganizationalUnitResource\Pages;

use App\Filament\Resources\OrganizationalUnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganizationalUnit extends CreateRecord
{
    protected static string $resource = OrganizationalUnitResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
