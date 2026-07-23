<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoodsInTransitResource\Pages;
use App\Models\GoodsInTransit;
use App\Models\Warehouse;
use App\Services\GoodsInTransitService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GoodsInTransitResource extends Resource
{
    public static function getModel(): string
    {
        return GoodsInTransit::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-truck';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المخزون' : 'Inventory';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'شحنة واردة' : 'Goods in Transit';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الشحنات الواردة' : 'Goods in Transit';
    }

    public static function canCreate(): bool
    {
        return ! auth()->user()->hasRole('executive');
    }

    public static function canEdit($record): bool
    {
        return ! auth()->user()->hasRole('executive');
    }

    public static function canDelete($record): bool
    {
        return ! auth()->user()->hasRole('executive');
    }

    public static function form(Schema $schema): Schema
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $schema->schema([
            Section::make($l('الشحنة', 'Shipment'))->schema([
                Forms\Components\TextInput::make('shipment_number')->label($l('رقم الشحنة', 'Shipment #'))->required()
                    ->helperText($l('المعرف الفريد للشحنة', 'Unique shipment identifier')),
                Forms\Components\Select::make('supplier_id')->label($l('المورد', 'Supplier'))->relationship('supplier', 'name_ar')->searchable()->required()
                    ->helperText($l('المورد الذي أرسل الشحنة', 'The supplier who sent the shipment')),
                Forms\Components\Select::make('status')->label($l('الحالة', 'Status'))->options([
                    'in_transit' => $l('قيد النقل', 'In transit'),
                    'at_customs' => $l('في الجمارك', 'At customs'),
                    'cleared' => $l('تم التخليص', 'Cleared'),
                    'partial_received' => $l('استلام جزئي', 'Partially received'),
                    'received' => $l('مستلم', 'Received'),
                ])->required()
                    ->helperText($l('الحالة الحالية للشحنة', 'Current status of the shipment')),
                Forms\Components\DatePicker::make('estimated_arrival_date')->label($l('الوصول المتوقع', 'ETA')),
                Forms\Components\DatePicker::make('posting_date')->label($l('تاريخ القيد', 'Posting Date'))->required()->default(now()),
            ])->columns(2),
            Section::make($l('تكاليف الوصول', 'Landed Costs'))->schema([
                Forms\Components\TextInput::make('shipping_cost')->label($l('الشحن', 'Shipping'))->numeric()->default(0)
                    ->helperText($l('تكلفة الشحن من المورد', 'Shipping cost from supplier')),
                Forms\Components\TextInput::make('freight_cost')->label($l('النقل', 'Freight'))->numeric()->default(0)
                    ->helperText($l('تكلفة النقل الداخلي', 'Internal freight cost')),
                Forms\Components\TextInput::make('customs_cost')->label($l('الجمارك', 'Customs'))->numeric()->default(0)
                    ->helperText($l('رسوم الجمارك والضرائب', 'Customs duties and taxes')),
                Forms\Components\TextInput::make('clearance_cost')->label($l('التخليص', 'Clearance'))->numeric()->default(0)
                    ->helperText($l('رسوم التخليص الجمركي', 'Customs clearance fees')),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shipment_number')->label($l('رقم الشحنة', 'Shipment #'))->searchable(),
                Tables\Columns\TextColumn::make('supplier.name_ar')->label($l('المورد', 'Supplier')),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label($l('الأصناف', 'Items')),
                Tables\Columns\TextColumn::make('landed_total')->label($l('إجمالي التكاليف', 'Landed Cost'))
                    ->state(fn (GoodsInTransit $r) => number_format(app(GoodsInTransitService::class)->totalLandedCost($r), 2)),
                Tables\Columns\BadgeColumn::make('status')->label($l('الحالة', 'Status'))
                    ->colors([
                        'warning' => 'in_transit',
                        'info' => ['at_customs', 'cleared', 'partial_received'],
                        'success' => 'received',
                    ]),
                Tables\Columns\TextColumn::make('estimated_arrival_date')->label($l('الوصول', 'ETA'))->date()->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label($l('الحالة', 'Status'))->options([
                    'in_transit' => $l('قيد النقل', 'In transit'),
                    'cleared' => $l('تم التخليص', 'Cleared'),
                    'received' => $l('مستلم', 'Received'),
                ]),
            ])
            ->defaultSort('posting_date', 'desc')
            ->actions([
                Action::make('receive')
                    ->label($l('استلام', 'Receive'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (GoodsInTransit $r) => ! in_array($r->status, ['received', 'cancelled'], true) && auth()->user()->hasAnyRole(['admin', 'warehouse_keeper', 'purchasing']))
                    ->form([
                        Forms\Components\Select::make('warehouse_id')->label($l('المستودع', 'Warehouse'))
                            ->options(fn () => Warehouse::where('company_id', Auth::user()->company_id)->where('type', 'main')->pluck('name_ar', 'id'))
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription($l('ستتم إضافة كميات الشحنة إلى المستودع المحدد.', 'The shipment quantities will be added to the selected warehouse.'))
                    ->action(function (GoodsInTransit $r, array $data) use ($l) {
                        try {
                            app(GoodsInTransitService::class)->receive($r, (int) $data['warehouse_id'], Auth::id());
                            Notification::make()->success()->title($l('تم الاستلام', 'Received into stock'))->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
                EditAction::make()
                    ->visible(fn () => ! auth()->user()->hasRole('executive')),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodsInTransit::route('/'),
            'create' => Pages\CreateGoodsInTransit::route('/create'),
            'edit' => Pages\EditGoodsInTransit::route('/{record}/edit'),
        ];
    }
}
