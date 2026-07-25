<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnRecordResource\Pages;
use App\Models\ReturnRecord;
use App\Services\ReturnService;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReturnRecordResource extends Resource
{
    public static function getModel(): string
    {
        return ReturnRecord::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-arrow-uturn-left';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'المرتجعات' : 'Returns';
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                TextColumn::make('return_number')->label(l('رقم', 'Number'))->searchable()->sortable(),
                TextColumn::make('customer.name_ar')->label(l('العميل', 'Customer'))->searchable(),
                TextColumn::make('user.name')->label(l('المستخدم', 'User')),
                TextColumn::make('total')->label(l('الإجمالي', 'Total'))->money('egp')->sortable(),
                BadgeColumn::make('status')->label(l('الحالة', 'Status'))
                    ->colors(['draft' => 'gray', 'submitted' => 'success', 'cancelled' => 'danger']),
                TextColumn::make('returned_at')->label(l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->filters([])
            ->defaultSort('returned_at', 'desc')
            ->actions([
                Action::make('cancel')
                    ->label(l('إلغاء', 'Cancel'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ReturnRecord $r) => $r->status === 'submitted')
                    ->requiresConfirmation()
                    ->modalHeading(l('إلغاء المرتجع', 'Cancel return'))
                    ->modalDescription(l('سيتم إلغاء هذا المرتجع وعكس أثره على المخزون ورصيد العميل. هذا الإجراء مُسجَّل ولا يمكن التراجع عنه.', 'This cancels the return and reverses its effect on stock and the customer balance. It is logged and cannot be undone.'))
                    ->modalSubmitActionLabel(l('تأكيد الإلغاء', 'Confirm cancel'))
                    ->action(fn (ReturnRecord $r) => app(ReturnService::class)->cancel($r, auth()->id(), __('app.admin_cancelled'))),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnRecords::route('/'),
        ];
    }
}
