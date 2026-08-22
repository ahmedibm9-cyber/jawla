<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrderResource\Pages;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\OrganizationScopeService;
use App\Services\SalesOrderService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any:sales_order') ?? false;
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    public static function getNavigationGroup(): ?string
    {
        return l('المبيعات', 'Sales');
    }

    public static function getLabel(): string
    {
        return l('أمر بيع', 'Sales order');
    }

    public static function getPluralLabel(): string
    {
        return l('أوامر البيع', 'Sales orders');
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
            Tables\Columns\TextColumn::make('order_number')->label(l('الرقم', 'Number'))->searchable(),
            Tables\Columns\TextColumn::make('customer.name_ar')->label(l('العميل', 'Customer'))->searchable(),
            Tables\Columns\TextColumn::make('user.name')->label(l('المندوب', 'Rep')),
            Tables\Columns\TextColumn::make('total')->label(l('الإجمالي', 'Total'))->money('egp'),
            Tables\Columns\TextColumn::make('status')->label(l('الحالة', 'Status'))->badge(),
            Tables\Columns\TextColumn::make('created_at')->label(l('التاريخ', 'Date'))->dateTime()->sortable(),
        ])->actions([
            Action::make('approve')->label(l('اعتماد', 'Approve'))->color('success')
                ->visible(fn (SalesOrder $record): bool => $record->status === 'submitted' && (auth()->user()?->can('sales_orders.approve') ?? false))
                ->requiresConfirmation()->modalDescription(l('سيصبح أمر البيع معتمداً وجاهزاً للتنفيذ. لا يُنشئ هذا الإجراء فاتورة ولا يخصم المخزون.', 'The order becomes approved and ready for fulfillment. This does not create an invoice or deduct stock.'))
                ->action(function (SalesOrder $record): void {
                    app(SalesOrderService::class)->approve($record->latestApproval, auth()->user());
                    Notification::make()->success()->title(l('تم الاعتماد', 'Approved'))->send();
                }),
            Action::make('reject')->label(l('رفض', 'Reject'))->color('danger')
                ->visible(fn (SalesOrder $record): bool => $record->status === 'submitted' && (auth()->user()?->can('sales_orders.approve') ?? false))
                ->form([Forms\Components\Textarea::make('reason')->label(l('السبب', 'Reason'))->required()->maxLength(1000)])
                ->requiresConfirmation()->modalDescription(l('سيُرفض أمر البيع ويظهر السبب للمندوب. الإجراء مُسجَّل.', 'The sales order will be rejected and the reason shown to the rep. The action is logged.'))
                ->action(fn (SalesOrder $record, array $data) => app(SalesOrderService::class)->reject($record->latestApproval, auth()->user(), $data['reason'])),
        ])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSalesOrders::route('/')];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['latestApproval.steps']);
        $actor = auth()->user();
        if ($actor === null) {
            return $query->whereRaw('1 = 0');
        }

        $visibleUsers = app(OrganizationScopeService::class)->scopeUsers(User::query()->select('users.id'), $actor);

        return $query->whereIn('user_id', $visibleUsers);
    }
}
