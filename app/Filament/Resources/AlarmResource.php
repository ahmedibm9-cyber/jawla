<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlarmResource\Pages;
use App\Models\Alarm;
use App\Models\Customer;
use App\Models\OutOfStockRequest;
use App\Models\User;
use App\Notifications\CustomerApprovalOutcome;
use App\Services\Contracts\AlarmService as AlarmServiceContract;
use App\Services\Contracts\OutOfStockService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AlarmResource extends Resource
{
    protected static ?string $model = Alarm::class;

    public static function getModel(): string
    {
        return Alarm::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-bell-alert';
    }

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'التنبيهات' : 'Alarms';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'التنبيهات' : 'Alarms';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'تنبيه' : 'Alarm';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Alarm::where('is_read', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return Alarm::where('is_read', false)->where('severity', 'critical')->exists()
            ? 'danger'
            : 'warning';
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('type')->label(l('النوع', 'Type'))
                    ->colors(['out_of_stock_request' => 'danger', 'customer_complaint' => 'danger', 'new_customer_pending' => 'warning', 'price_quotation_requested' => 'info', 'purchase_request' => 'success', 'goods_in_transit_delayed' => 'warning', 'batch_expiring' => 'warning']),
                Tables\Columns\BadgeColumn::make('severity')->label(l('المستوى', 'Severity'))
                    ->colors(['critical' => 'danger', 'warning' => 'warning', 'info' => 'primary']),
                Tables\Columns\TextColumn::make('title')->label(l('العنوان', 'Title'))->searchable(),
                Tables\Columns\TextColumn::make('description')->label(l('الوصف', 'Description'))->limit(60),
                Tables\Columns\IconColumn::make('is_read')->label(l('مقروء', 'Read'))->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label(l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('severity')
                    ->label(l('المستوى', 'Severity'))
                    ->options([
                        'critical' => l('حرج', 'Critical'),
                        'warning' => l('تحذير', 'Warning'),
                        'info' => l('معلومة', 'Info'),
                    ]),
                SelectFilter::make('type')
                    ->label(l('النوع', 'Type'))
                    ->options([
                        'out_of_stock_request' => l('نفاد مخزون', 'Out of Stock'),
                        'customer_complaint' => l('شكوى عميل', 'Complaint'),
                        'new_customer_pending' => l('عميل جديد معلق', 'Pending Customer'),
                        'price_quotation_requested' => l('طلب تسعير', 'Price Request'),
                        'purchase_request' => l('طلب شراء', 'Purchase Request'),
                    ]),
                TernaryFilter::make('is_read')
                    ->label(l('مقروء', 'Read')),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // Customer approval: Accept button
                Action::make('accept_customer')
                    ->label(l('قبول', 'Accept'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->tooltip(l('اعتماد العميل الجديد', 'Approve the new customer'))
                    ->visible(fn (Alarm $a) => $a->type === 'new_customer_pending'
                        && ! $a->is_read
                        && static::canRespond())
                    ->requiresConfirmation()
                    ->modalHeading(fn (Alarm $a) => l(
                        "قبول العميل: {$a->title}",
                        "Accept Customer: {$a->title}",
                    ))
                    ->modalDescription(fn (Alarm $a) => l(
                        'سيتم اعتماد العميل الجديد والموافقة عليه',
                        'The new customer will be approved and activated',
                    ))
                    ->action(function (Alarm $a): void {
                        abort_unless(static::canRespond(), 403);

                        if ($a->reference_type === Customer::class) {
                            $customer = Customer::withoutGlobalScopes()
                                ->where('company_id', $a->company_id)
                                ->find($a->reference_id);

                            if ($customer !== null) {
                                $customer->update([
                                    'status' => 'approved',
                                    'approved_by' => auth()->id(),
                                    'approved_at' => now(),
                                ]);

                                User::find($customer->added_by)
                                    ?->notify(new CustomerApprovalOutcome($customer, 'approved'));
                            }
                        }

                        app(AlarmServiceContract::class)->resolve($a, auth()->id());

                        Notification::make()->title(l('تم قبول العميل', 'Customer Accepted'))->success()->send();
                    }),

                // Customer approval: Reject button
                Action::make('reject_customer')
                    ->label(l('رفض', 'Reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->tooltip(l('رفض العميل الجديد', 'Reject the new customer'))
                    ->visible(fn (Alarm $a) => $a->type === 'new_customer_pending'
                        && ! $a->is_read
                        && static::canRespond())
                    ->requiresConfirmation()
                    ->modalHeading(fn (Alarm $a) => l(
                        "رفض العميل: {$a->title}",
                        "Reject Customer: {$a->title}",
                    ))
                    ->modalDescription(fn (Alarm $a) => l(
                        'سيتم رفض العميل الجديد وتعطيل حسابه',
                        'The new customer will be rejected and deactivated',
                    ))
                    ->action(function (Alarm $a): void {
                        abort_unless(static::canRespond(), 403);

                        if ($a->reference_type === Customer::class) {
                            $customer = Customer::withoutGlobalScopes()
                                ->where('company_id', $a->company_id)
                                ->find($a->reference_id);

                            if ($customer !== null) {
                                $customer->update([
                                    'status' => 'rejected',
                                    'is_active' => false,
                                    'rejected_by' => auth()->id(),
                                    'rejected_at' => now(),
                                ]);

                                User::find($customer->added_by)
                                    ?->notify(new CustomerApprovalOutcome($customer, 'rejected'));
                            }
                        }

                        app(AlarmServiceContract::class)->resolve($a, auth()->id());

                        Notification::make()->title(l('تم رفض العميل', 'Customer Rejected'))->success()->send();
                    }),

                // Generic: Acknowledge (for non-customer alarms)
                Action::make('acknowledge')
                    ->label(l('استلام', 'Acknowledge'))
                    ->icon('heroicon-o-check')
                    ->color('warning')
                    ->tooltip(l('تسجيل استلام التنبيه', 'Record acknowledgment of this alarm'))
                    ->visible(fn (Alarm $a) => $a->type !== 'new_customer_pending'
                        && ! $a->is_read
                        && static::canRespond())
                    ->requiresConfirmation()
                    ->modalDescription(fn (Alarm $a) => l(
                        "سيتم تسجيل استلامك لهذا التنبيه: {$a->title}",
                        "You will be recorded as acknowledging this alarm: {$a->title}",
                    ))
                    ->action(function (Alarm $a): void {
                        abort_unless(static::canRespond(), 403);

                        app(AlarmServiceContract::class)->acknowledge($a, auth()->id());

                        Notification::make()->title(l('تم الاستلام', 'Acknowledged'))->success()->send();
                    }),

                // Generic: Resolve (for non-customer alarms)
                Action::make('resolve')
                    ->label(l('حل', 'Resolve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->tooltip(l('إغلاق التنبيه نهائياً', 'Close this alarm permanently'))
                    ->visible(fn (Alarm $a) => $a->type !== 'new_customer_pending'
                        && static::canRespond())
                    ->requiresConfirmation()
                    ->modalDescription(fn (Alarm $a) => l(
                        "سيتم إغلاق هذا التنبيه نهائياً: {$a->title}",
                        "This alarm will be closed permanently: {$a->title}",
                    ))
                    ->action(function (Alarm $a): void {
                        abort_unless(static::canRespond(), 403);

                        app(AlarmServiceContract::class)->resolve($a, auth()->id());

                        if ($a->reference_type === OutOfStockRequest::class) {
                            $request = OutOfStockRequest::withoutGlobalScopes()
                                ->where('company_id', $a->company_id)
                                ->find($a->reference_id);

                            if ($request !== null) {
                                app(OutOfStockService::class)->resolve($request, auth()->id());
                            }
                        }

                        Notification::make()->title(l('تم الحل', 'Resolved'))->success()->send();
                    }),
            ]);
    }

    public static function canRespond(): bool
    {
        $user = auth()->user();

        return $user?->can('alarms.respond') ?? false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAlarms::route('/')];
    }
}
