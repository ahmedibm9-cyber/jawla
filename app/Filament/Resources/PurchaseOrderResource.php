<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    public static function getModel(): string
    {
        return PurchaseOrder::class;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any:purchase_order') ?? false;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'أمر شراء' : 'Purchase Order';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'أوامر الشراء' : 'Purchase Orders';
    }

    // POs are generated exclusively by PurchaseRequestService on purchasing
    // approval; this resource is a read-only register.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {

        return $schema->schema([
            Section::make(l('بيانات أمر الشراء', 'Purchase Order Info'))->schema([
                Forms\Components\TextInput::make('order_number')->label(l('رقم الأمر', 'Order Number'))->disabled(),
                Forms\Components\TextInput::make('status')->label(l('الحالة', 'Status'))->disabled(),
                Forms\Components\Select::make('supplier_id')->label(l('المورد', 'Supplier'))
                    ->relationship('supplier', 'name_ar')->disabled(),
                Forms\Components\TextInput::make('order_date')->label(l('تاريخ الأمر', 'Order Date'))->disabled(),
                Forms\Components\TextInput::make('currency')->label(l('العملة', 'Currency'))->disabled(),
                Forms\Components\TextInput::make('subtotal')->label(l('الإجمالي الفرعي', 'Subtotal'))->disabled(),
                Forms\Components\TextInput::make('total')->label(l('الإجمالي', 'Total'))->disabled(),
                Forms\Components\Textarea::make('payment_terms')->label(l('شروط الدفع', 'Payment Terms'))->disabled(),
                Forms\Components\Textarea::make('notes')->label(l('ملاحظات', 'Notes'))->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label(l('رقم الأمر', 'Order #'))->searchable(),
                Tables\Columns\TextColumn::make('supplier.name_ar')->label(l('المورد', 'Supplier'))
                    ->formatStateUsing(fn ($s, PurchaseOrder $r) => $r->supplier?->name_ar ?? $r->supplier?->name_en ?? '—'),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label(l('البنود', 'Items')),
                Tables\Columns\TextColumn::make('total')->label(l('الإجمالي', 'Total'))->numeric(2),
                Tables\Columns\BadgeColumn::make('status')->label(l('الحالة', 'Status'))
                    ->colors([
                        'draft' => 'warning',
                        'sent' => 'info',
                        'confirmed' => 'success',
                        'partial' => 'info',
                        'received' => 'success',
                        'cancelled' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('order_date')->label(l('التاريخ', 'Date'))->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(l('الحالة', 'Status'))
                    ->options([
                        'draft' => l('مسودة', 'Draft'),
                        'sent' => l('مرسل', 'Sent'),
                        'confirmed' => l('مؤكد', 'Confirmed'),
                        'partial' => l('مستلم جزئيًا', 'Partially Received'),
                        'received' => l('مستلم', 'Received'),
                        'cancelled' => l('ملغي', 'Cancelled'),
                    ]),
            ])
            ->defaultSort('order_date', 'desc')
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
        ];
    }
}
