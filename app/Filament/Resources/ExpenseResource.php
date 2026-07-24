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
                TextColumn::make('note')->label(l('ملاحظات', 'Note'))->limit(40)->searchable(),
                TextColumn::make('spent_at')->label(l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->filters([])
            ->defaultSort('spent_at', 'desc')
            ->actions([
                Action::make('cancel')
                    ->label(l('إلغاء', 'Cancel'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Expense $r) => $r->cancelled_at === null)
                    ->requiresConfirmation()
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
