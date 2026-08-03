<?php

namespace App\Filament\Resources;

use App\Enums\DeviceStatus;
use App\Filament\Resources\DeviceResource\Pages;
use App\Models\Device;
use App\Services\DeviceService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    public static function getNavigationGroup(): ?string
    {
        return l('الشركة', 'Company');
    }

    public static function getLabel(): string
    {
        return l('جهاز', 'Device');
    }

    public static function getPluralLabel(): string
    {
        return l('الأجهزة', 'Devices');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label(l('الجهاز', 'Device'))->searchable(),
            Tables\Columns\TextColumn::make('user.name')->label(l('المستخدم', 'User'))->searchable(),
            Tables\Columns\TextColumn::make('platform')->label(l('المنصة', 'Platform')),
            Tables\Columns\TextColumn::make('status')->label(l('الحالة', 'Status'))->badge()
                ->color(fn (DeviceStatus|string $state): string => match ($state instanceof DeviceStatus ? $state : DeviceStatus::from($state)) {
                    DeviceStatus::Approved => 'success', DeviceStatus::Pending => 'warning', DeviceStatus::Revoked => 'danger',
                }),
            Tables\Columns\TextColumn::make('last_seen_at')->label(l('آخر ظهور', 'Last seen'))->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('created_at')->label(l('تاريخ التسجيل', 'Registered'))->dateTime()->sortable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options([
                'pending' => l('بانتظار الاعتماد', 'Pending'),
                'approved' => l('معتمد', 'Approved'),
                'revoked' => l('ملغي', 'Revoked'),
            ]),
        ])->actions([
            Action::make('approve')->label(l('اعتماد', 'Approve'))->color('success')
                ->visible(fn (Device $record): bool => $record->status !== DeviceStatus::Approved && (auth()->user()?->can('devices.approve') ?? false))
                ->requiresConfirmation()
                ->modalDescription(l('سيُسمح لهذا الجهاز بالوصول إلى تطبيق الميدان لهذا المستخدم. الإجراء مُسجَّل.', 'This device will be allowed to access the field app for this user. The action is logged.'))
                ->action(function (Device $record): void {
                    app(DeviceService::class)->approve($record, auth()->user());
                    Notification::make()->success()->title(l('تم اعتماد الجهاز', 'Device approved'))->send();
                }),
            Action::make('revoke')->label(l('إلغاء الوصول', 'Revoke'))->color('danger')
                ->visible(fn (Device $record): bool => $record->status === DeviceStatus::Approved && (auth()->user()?->can('devices.approve') ?? false))
                ->requiresConfirmation()
                ->modalDescription(l('سيتوقف هذا الجهاز فوراً عن الوصول إلى تطبيق الميدان. الإجراء مُسجَّل.', 'This device will immediately lose access to the field app. The action is logged.'))
                ->action(function (Device $record): void {
                    app(DeviceService::class)->revoke($record, auth()->user());
                    Notification::make()->success()->title(l('تم إلغاء وصول الجهاز', 'Device access revoked'))->send();
                }),
        ])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListDevices::route('/')];
    }
}
