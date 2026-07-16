<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Imports\StockImport;
use App\Models\Stock;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

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

    public static function form(Schema $schema): Schema
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return Schema::make()
            ->schema([
                Select::make('warehouse_id')
                    ->label($l('المستودع', 'Warehouse'))
                    ->relationship('warehouse', 'name_ar')
                    ->required()
                    ->preload(),

                Select::make('product_id')
                    ->label($l('المنتج', 'Product'))
                    ->relationship('product', 'name_ar')
                    ->required()
                    ->preload(),

                Select::make('batch_id')
                    ->label($l('التشغيلة', 'Batch'))
                    ->relationship('batch', 'batch_number')
                    ->preload()
                    ->searchable(),

                TextInput::make('quantity')
                    ->label($l('الكمية', 'Quantity'))
                    ->numeric()
                    ->step(0.001)
                    ->default(0)
                    ->required(),

                Toggle::make('is_reserved')
                    ->label($l('محجوز', 'Reserved'))
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return Table::make()
            ->columns([
                TextColumn::make('warehouse.name_ar')
                    ->label($l('المستودع', 'Warehouse'))
                    ->searchable(),
                TextColumn::make('product.name_ar')
                    ->label($l('المنتج', 'Product'))
                    ->searchable(),
                TextColumn::make('batch.batch_number')
                    ->label($l('التشغيلة', 'Batch'))
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label($l('الكمية', 'Quantity'))
                    ->numeric(3)
                    ->sortable()
                    ->color(fn ($state) => $state <= 5 ? 'danger' : ($state <= 20 ? 'warning' : 'success')),
                IconColumn::make('is_reserved')
                    ->label($l('محجوز', 'Reserved'))
                    ->boolean(),
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
                TernaryFilter::make('is_reserved'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->headerActions([
                Action::make('import')
                    ->label(fn () => app()->getLocale() === 'ar' ? 'استيراد المخزون' : 'Import Stock')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        FileUpload::make('file')
                            ->label(fn () => app()->getLocale() === 'ar' ? 'ملف CSV/Excel' : 'CSV/Excel File')
                            ->acceptedFileTypes(['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->required()
                            ->maxSize(10240),
                        Select::make('warehouse_id')
                            ->label(fn () => app()->getLocale() === 'ar' ? 'المستودع' : 'Warehouse')
                            ->relationship('warehouse', 'name_ar')
                            ->required()
                            ->preload(),
                        Toggle::make('update_existing')
                            ->label(fn () => app()->getLocale() === 'ar' ? 'تحديث السجلات الموجودة' : 'Update Existing Records')
                            ->default(true),
                    ])
                    ->action(function (array $data) {
                        $path = $data['file'];
                        $warehouseId = $data['warehouse_id'];
                        $updateExisting = $data['update_existing'];

                        $import = new StockImport($warehouseId, $updateExisting);
                        Excel::import($import, $path);

                        return [
                            'imported' => $import->getImportedCount(),
                            'updated' => $import->getUpdatedCount(),
                            'skipped' => $import->getSkippedCount(),
                        ];
                    })
                    ->successNotification(
                        Notification::make()
                            ->title(fn (array $data) => app()->getLocale() === 'ar'
                                ? "تم استيراد {$data['imported']} سجل، تحديث {$data['updated']} سجل"
                                : "Imported {$data['imported']} records, updated {$data['updated']} records")
                            ->success(),
                    ),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStocks::route('/'),
            'create' => Pages\CreateStock::route('/create'),
            'edit' => Pages\EditStock::route('/{record}/edit'),
        ];
    }
}
