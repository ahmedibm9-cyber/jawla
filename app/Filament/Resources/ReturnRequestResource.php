<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnRequestResource\Pages;
use App\Models\ReturnRequest;
use App\Services\ReturnRequestService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ReturnRequestResource extends Resource
{
    protected static ?string $model = ReturnRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    public static function getNavigationGroup(): ?string
    {
        return l('المبيعات', 'Sales');
    }

    public static function getLabel(): string
    {
        return l('طلب مرتجع', 'Return request');
    }

    public static function getPluralLabel(): string
    {
        return l('طلبات المرتجعات', 'Return requests');
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
            Tables\Columns\TextColumn::make('request_number')->label(l('الرقم', 'Number'))->searchable(),
            Tables\Columns\TextColumn::make('customer.name_ar')->label(l('العميل', 'Customer'))->searchable(),
            Tables\Columns\TextColumn::make('user.name')->label(l('المندوب', 'Rep')),
            Tables\Columns\TextColumn::make('total')->label(l('القيمة', 'Value'))->money('egp'),
            Tables\Columns\TextColumn::make('status')->label(l('الحالة', 'Status'))->badge(),
            Tables\Columns\TextColumn::make('photos_count')->counts('photos')->label(l('الصور', 'Photos')),
            Tables\Columns\TextColumn::make('submitted_at')->label(l('تاريخ الطلب', 'Submitted'))->dateTime()->sortable(),
        ])->actions([
            Action::make('approve')->label(l('اعتماد', 'Approve'))->color('success')
                ->visible(fn (ReturnRequest $record): bool => $record->status === 'pending_approval' && (auth()->user()?->can('return_requests.approve') ?? false))
                ->requiresConfirmation()->modalDescription(l('سيُعتمد طلب المرتجع فقط. لن يتغير المخزون أو رصيد العميل حتى يسجّل المخزن الاستلام.', 'This approves the request only. Stock and customer balances remain unchanged until warehouse receipt.'))
                ->action(function (ReturnRequest $record): void {
                    app(ReturnRequestService::class)->approve($record->latestApproval, auth()->user());
                    Notification::make()->success()->title(l('تم الاعتماد', 'Return approved'))->send();
                }),
            Action::make('reject')->label(l('رفض', 'Reject'))->color('danger')
                ->visible(fn (ReturnRequest $record): bool => $record->status === 'pending_approval' && (auth()->user()?->can('return_requests.approve') ?? false))
                ->form([Forms\Components\Textarea::make('reason')->label(l('السبب', 'Reason'))->required()->maxLength(1000)])
                ->requiresConfirmation()->modalDescription(l('سيُرفض الطلب ولن تحدث أي حركة مخزون أو حركة مالية. الإجراء مُسجَّل.', 'The request will be rejected with no stock or financial movement. The action is logged.'))
                ->action(fn (ReturnRequest $record, array $data) => app(ReturnRequestService::class)->reject($record->latestApproval, auth()->user(), $data['reason'])),
            Action::make('receive')->label(l('استلام في المخزن', 'Warehouse receipt'))->color('success')
                ->visible(fn (ReturnRequest $record): bool => $record->status === 'approved' && (auth()->user()?->can('return_requests.receive') ?? false))
                ->form([Forms\Components\Textarea::make('notes')->label(l('ملاحظات الاستلام', 'Receipt notes'))->maxLength(1000)])
                ->requiresConfirmation()->modalDescription(l('سيؤدي الاستلام إلى إضافة الأصناف الصالحة للمخزون والتالفة للحجر، وإصدار الإشعار الدائن وتحديث رصيد العميل. هذا إجراء مالي ومخزني مُسجَّل.', 'Receipt adds sellable goods to stock, damaged goods to quarantine, issues the credit note, and updates the customer balance. This is a logged stock and financial action.'))
                ->action(function (ReturnRequest $record, array $data): void {
                    app(ReturnRequestService::class)->receive($record, auth()->user(), $data['notes'] ?? null);
                    Notification::make()->success()->title(l('تم استلام المرتجع', 'Return received'))->send();
                }),
        ])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListReturnRequests::route('/')];
    }
}
