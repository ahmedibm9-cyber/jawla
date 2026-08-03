<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookEndpointResource\Pages;
use App\Models\WebhookEndpoint;
use App\Rules\SafeWebhookUrl;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class WebhookEndpointResource extends Resource
{
    protected static ?string $model = WebhookEndpoint::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    public static function getNavigationGroup(): ?string
    {
        return l('النظام', 'System');
    }

    public static function getLabel(): string
    {
        return l('نقطة Webhook', 'Webhook endpoint');
    }

    public static function getPluralLabel(): string
    {
        return l('Webhooks', 'Webhooks');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('integrations.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([Section::make(l('إعداد التكامل', 'Integration settings'))->schema([
            Forms\Components\TextInput::make('name')->label(l('الاسم', 'Name'))->required()->maxLength(255),
            Forms\Components\TextInput::make('url')->label('HTTPS URL')->url()->rule(new SafeWebhookUrl)->required()->maxLength(2000),
            Forms\Components\TextInput::make('secret')->label(l('سر التوقيع', 'Signing secret'))->password()
                ->required(fn ($record): bool => $record === null)->dehydrated(fn ($state): bool => filled($state))->maxLength(500),
            Forms\Components\CheckboxList::make('events')->label(l('الأحداث', 'Events'))->options([
                'task.approved' => 'Task approved',
                'sales_order.approved' => 'Sales order approved',
                'collection.approved' => 'Collection approved',
                'return.received' => 'Return received',
            ])->required()->columns(2),
            Forms\Components\TextInput::make('timeout_seconds')->label(l('مهلة الاتصال بالثواني', 'Timeout seconds'))->numeric()->minValue(1)->maxValue(30)->default(10),
            Forms\Components\Toggle::make('is_active')->label(l('نشط', 'Active'))->default(true),
            Forms\Components\Hidden::make('created_by')->default(fn () => auth()->id()),
        ])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label(l('الاسم', 'Name'))->searchable(),
            Tables\Columns\TextColumn::make('url')->label('URL')->limit(50),
            Tables\Columns\TextColumn::make('events')->label(l('الأحداث', 'Events'))->badge(),
            Tables\Columns\IconColumn::make('is_active')->label(l('نشط', 'Active'))->boolean(),
            Tables\Columns\TextColumn::make('deliveries_count')->counts('deliveries')->label(l('عمليات التسليم', 'Deliveries')),
        ])->actions([EditAction::make()])->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListWebhookEndpoints::route('/'), 'create' => Pages\CreateWebhookEndpoint::route('/create'), 'edit' => Pages\EditWebhookEndpoint::route('/{record}/edit')];
    }
}
