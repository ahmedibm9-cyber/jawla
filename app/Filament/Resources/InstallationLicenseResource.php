<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstallationLicenseResource\Pages;
use App\Models\InstallationLicense;
use App\Services\LicenseService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InstallationLicenseResource extends Resource
{
    protected static ?string $model = InstallationLicense::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any:installation_license') ?? false;
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    public static function getNavigationGroup(): ?string
    {
        return l('النظام', 'System');
    }

    public static function getLabel(): string
    {
        return l('ترخيص التثبيت', 'Installation license');
    }

    public static function getPluralLabel(): string
    {
        return l('ترخيص التثبيت', 'Installation license');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('licenses.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('licenses.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('license_id')->label(l('معرف الترخيص', 'License ID'))->copyable(),
            Tables\Columns\TextColumn::make('licensee')->label(l('المرخص له', 'Licensee')),
            Tables\Columns\TextColumn::make('edition')->label(l('الإصدار', 'Edition'))->badge(),
            Tables\Columns\TextColumn::make('max_users')->label(l('حد المستخدمين', 'User limit')),
            Tables\Columns\TextColumn::make('expires_at')->label(l('ينتهي في', 'Expires'))->date(),
            Tables\Columns\TextColumn::make('status')->label(l('الحالة', 'Status'))->badge(),
            Tables\Columns\TextColumn::make('last_verified_at')->label(l('آخر تحقق', 'Last verified'))->dateTime(),
        ])->actions([
            Action::make('verify')->label(l('تحقق الآن', 'Verify now'))->requiresConfirmation()
                ->action(function (InstallationLicense $record): void {
                    app(LicenseService::class)->verify($record);
                    Notification::make()->success()->title(l('الترخيص صالح', 'License verified'))->send();
                }),
        ])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListInstallationLicenses::route('/'), 'create' => Pages\CreateInstallationLicense::route('/create')];
    }
}
