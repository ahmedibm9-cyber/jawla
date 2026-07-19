<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    public static function getModel(): string
    {
        return Payment::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'التحصيلات' : 'Payments';
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('customer.name_ar')->label($l('العميل', 'Customer'))->searchable(),
                TextColumn::make('invoice.invoice_number')->label($l('الفاتورة', 'Invoice'))->searchable(),
                TextColumn::make('user.name')->label($l('المحصل', 'Collected by')),
                TextColumn::make('amount')->label($l('المبلغ', 'Amount'))->money('egp')->sortable(),
                BadgeColumn::make('method')->label($l('الوسيلة', 'Method'))
                    ->colors(['cash' => 'success', 'cheque' => 'warning', 'transfer' => 'info', 'other' => 'gray']),
                TextColumn::make('collected_at')->label($l('التاريخ', 'Date'))->dateTime()->sortable(),
                BadgeColumn::make('is_cancelled')->label($l('الحالة', 'Status'))
                    ->getStateUsing(fn (Payment $r) => $r->cancelled_at ? 'cancelled' : 'active')
                    ->colors(['active' => 'success', 'cancelled' => 'danger'])
                    ->formatStateUsing(fn (?string $s) => $s === 'cancelled' ? ($l('ملغي', 'Cancelled')) : ($l('نشط', 'Active'))),
            ])
            ->filters([])
            ->defaultSort('collected_at', 'desc')
            ->actions([
                Action::make('cancel')
                    ->label($l('إلغاء', 'Cancel'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Payment $r) => ! $r->cancelled_at)
                    ->requiresConfirmation()
                    ->action(fn (Payment $r) => app(PaymentService::class)->cancel($r, auth()->id(), 'Admin cancelled')),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}
