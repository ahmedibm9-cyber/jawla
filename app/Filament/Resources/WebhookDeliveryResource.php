<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookDeliveryResource\Pages;
use App\Models\WebhookDelivery;
use App\Services\LicenseService;
use App\Services\WebhookService;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class WebhookDeliveryResource extends Resource
{
    protected static ?string $model = WebhookDelivery::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    public static function getNavigationGroup(): ?string
    {
        return l('النظام', 'System');
    }

    public static function getLabel(): string
    {
        return l('سجل تكامل', 'Integration log');
    }

    public static function getPluralLabel(): string
    {
        return l('سجل التكاملات', 'Integration logs');
    }

    public static function canViewAny(): bool
    {
        return (auth()->user()?->can('integrations.manage') ?? false)
            && app(LicenseService::class)->runtimeFeatureEnabled('webhooks');
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
            Tables\Columns\TextColumn::make('event_id')->label(l('معرف الحدث', 'Event ID'))->copyable(),
            Tables\Columns\TextColumn::make('endpoint.name')->label(l('النقطة', 'Endpoint')),
            Tables\Columns\TextColumn::make('event_type')->label(l('الحدث', 'Event'))->badge(),
            Tables\Columns\TextColumn::make('status')->label(l('الحالة', 'Status'))->badge(),
            Tables\Columns\TextColumn::make('attempts')->label(l('المحاولات', 'Attempts')),
            Tables\Columns\TextColumn::make('http_status')->label('HTTP'),
            Tables\Columns\TextColumn::make('created_at')->label(l('التاريخ', 'Date'))->dateTime()->sortable(),
        ])->actions([
            Action::make('retry')->label(l('إعادة المحاولة', 'Retry'))->visible(fn (WebhookDelivery $record): bool => $record->status === 'failed' && $record->attempts < 5)
                ->requiresConfirmation()->action(fn (WebhookDelivery $record) => app(WebhookService::class)->attempt($record)),
        ])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListWebhookDeliveries::route('/')];
    }
}
