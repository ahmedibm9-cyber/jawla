<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Services\ExpenseService;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    public static function getModel(): string
    {
        return Expense::class;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any:expense') ?? false;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-receipt-percent';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المالية' : 'Finance';
    }

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'المصروفات' : 'Expenses';
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('user.name')->label(l('المستخدم', 'User'))->searchable(),
                BadgeColumn::make('category')->label(l('النوع', 'Category'))
                    ->colors(['fuel' => 'warning', 'maintenance' => 'info', 'food' => 'success', 'other' => 'gray']),
                TextColumn::make('amount')->label(l('المبلغ', 'Amount'))->money('egp')->sortable(),
                BadgeColumn::make('status')->label(l('الحالة', 'Status'))
                    ->colors(['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger']),
                TextColumn::make('note')->label(l('ملاحظات', 'Note'))->limit(40)->searchable(),
                TextColumn::make('spent_at')->label(l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->filters([])
            ->defaultSort('spent_at', 'desc')
            ->actions([
                Action::make('approve')
                    ->label(l('اعتماد', 'Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Expense $r) => $r->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading(l('اعتماد المصروف', 'Approve expense'))
                    ->modalDescription(l('سيتم اعتماد هذا المصروف وخصم المبلغ من صندوق النقدية.', 'This expense will be approved and the amount deducted from the cash box.'))
                    ->modalSubmitActionLabel(l('تأكيد الاعتماد', 'Confirm approve'))
                    ->action(fn (Expense $r) => app(ExpenseService::class)->approve($r, auth()->id())),

                Action::make('reject')
                    ->label(l('رفض', 'Reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Expense $r) => $r->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading(l('رفض المصروف', 'Reject expense'))
                    ->modalDescription(l('سيتم رفض هذا المصروف. لن يتم خصم أي مبلغ.', 'This expense will be rejected. No amount will be deducted.'))
                    ->modalSubmitActionLabel(l('تأكيد الرفض', 'Confirm reject'))
                    ->action(fn (Expense $r) => app(ExpenseService::class)->reject($r, auth()->id())),

                Action::make('cancel')
                    ->label(l('إلغاء', 'Cancel'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Expense $r) => $r->cancelled_at === null && $r->status !== 'rejected')
                    ->requiresConfirmation()
                    ->modalHeading(l('إلغاء المصروف', 'Cancel expense'))
                    ->modalDescription(l('سيتم إلغاء هذا المصروف وإعادة مبلغه إلى صندوق النقدية. هذا الإجراء مُسجَّل ولا يمكن التراجع عنه.', 'This cancels the expense and credits its amount back to the cash box. It is logged and cannot be undone.'))
                    ->modalSubmitActionLabel(l('تأكيد الإلغاء', 'Confirm cancel'))
                    ->action(fn (Expense $r) => app(ExpenseService::class)->cancel($r, auth()->id())),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
        ];
    }
}
