<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnRecordResource\Pages;
use App\Models\ReturnRecord;
use Filament\Resources\Resource;
use Filament\Actions\Action;
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
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                TextColumn::make('return_number')->label($l('رقم', 'Number'))->searchable()->sortable(),
                TextColumn::make('customer.name_ar')->label($l('العميل', 'Customer'))->searchable(),
                TextColumn::make('user.name')->label($l('المستخدم', 'User')),
                TextColumn::make('total')->label($l('الإجمالي', 'Total'))->money('egp')->sortable(),
                BadgeColumn::make('status')->label($l('الحالة', 'Status'))
                    ->colors(['draft' => 'gray', 'submitted' => 'success', 'cancelled' => 'danger']),
                TextColumn::make('returned_at')->label($l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->filters([])
            ->defaultSort('returned_at', 'desc')
            ->actions([
                Action::make('cancel')
                    ->label($l('إلغاء', 'Cancel'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ReturnRecord $r) => $r->status === 'submitted')
                    ->requiresConfirmation()
                    ->action(fn (ReturnRecord $r) => app(\App\Services\ReturnService::class)->cancel($r, auth()->id(), __('app.admin_cancelled'))),
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
