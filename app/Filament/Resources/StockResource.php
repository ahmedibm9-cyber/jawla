<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Models\Stock;
use App\Services\Contracts\StockService as StockServiceContract;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockResource extends Resource
{
    public static function getModel(): string
    {
        return Stock::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cube';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المخزون' : 'Inventory';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'رصيد المخزون' : 'Stock Balance';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'أرصدة المخزون' : 'Stock Balances';
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                TextColumn::make('warehouse.name_ar')
                    ->label($l('المستودع', 'Warehouse'))
                    ->searchable(),
                TextColumn::make('product.name_ar')
                    ->label($l('المنتج', 'Product'))
                    ->searchable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('batch.batch_number')
                    ->label($l('التشغيلة', 'Batch'))
                    ->placeholder('—'),
                TextColumn::make('quantity')
                    ->label($l('الكمية', 'Quantity'))
                    ->numeric(3)
                    ->sortable()
                    ->color(fn ($state) => $state <= 5 ? 'danger' : ($state <= 20 ? 'warning' : 'success')),
                TextColumn::make('updated_at')
                    ->label($l('آخر تحديث', 'Updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label($l('المستودع', 'Warehouse'))
                    ->relationship('warehouse', 'name_ar'),
                SelectFilter::make('product_id')
                    ->label($l('المنتج', 'Product'))
                    ->relationship('product', 'name_ar'),
            ])
            ->actions([
                TableAction::make('adjust')
                    ->label($l('تسوية', 'Adjust'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->visible(fn () => auth()->user()->hasAnyRole(['admin', 'warehouse_keeper']))
                    ->requiresConfirmation()
                    ->modalDescription(fn () => app()->getLocale() === 'ar'
                        ? 'سيقوم هذا بإنشاء حركة مخزون مطابقة. لا يمكن التراجع.'
                        : 'This will create a matching stock movement. Cannot be undone.')
                    ->form([
                        TextInput::make('counted_quantity')
                            ->label($l('الكمية الفعلية', 'Counted Quantity'))
                            ->numeric()
                            ->step(0.001)
                            ->required(),
                        Textarea::make('reason')
                            ->label($l('السبب', 'Reason'))
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Stock $record, array $data) {
                        app(StockServiceContract::class)->reconcile(
                            $record->warehouse_id,
                            $record->product_id,
                            $record->batch_id,
                            (float) $data['counted_quantity'],
                            $data['reason'],
                            auth()->id(),
                        );

                        Notification::make()
                            ->title($l('تمت التسوية', 'Stock adjusted'))
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStocks::route('/'),
        ];
    }
}
