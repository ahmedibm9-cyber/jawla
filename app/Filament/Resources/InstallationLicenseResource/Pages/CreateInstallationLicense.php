<?php

namespace App\Filament\Resources\InstallationLicenseResource\Pages;

use App\Filament\Resources\InstallationLicenseResource;
use App\Models\InstallationLicense;
use App\Services\LicenseService;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateInstallationLicense extends CreateRecord
{
    protected static string $resource = InstallationLicenseResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Textarea::make('document')->label(l('مستند الترخيص JSON', 'License JSON document'))->required()->rows(12),
            Forms\Components\Textarea::make('signature')->label(l('التوقيع Base64', 'Base64 signature'))->required()->rows(5),
        ]);
    }

    protected function handleRecordCreation(array $data): InstallationLicense
    {
        return app(LicenseService::class)->install($data['document'], $data['signature'], auth()->user());
    }
}
