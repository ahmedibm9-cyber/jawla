<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseRequestResource\Pages;
use App\Models\PurchaseRequest;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestResource extends Resource
{
    public static function getModel(): string
    {
        return PurchaseRequest::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-shopping-cart';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'عرض شراء' : 'Purchase Offer';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'عروض الشراء' : 'Purchase Offers';
    }

    public static function form(Schema $schema): Schema
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $schema->schema([
            Forms\Components\Section::make($l('بيانات', 'Info'))->schema([
                Forms\Components\Select::make('user_id')->label($l('المندوب', 'Rep'))
                    ->relationship('user', 'name')->preload(),
                Forms\Components\Select::make('supplier_id')->label($l('المورد', 'Supplier'))
                    ->relationship('supplier', 'name')->preload(),
                Forms\Components\Select::make('product_id')->label($l('المنتج', 'Product'))
                    ->relationship('product', 'name_ar')->preload(),
                Forms\Components\TextInput::make('quantity')->label($l('الكمية', 'Quantity'))->numeric(),
                Forms\Components\TextInput::make('offered_price')->label($l('السعر المعروض', 'Offered Price'))->numeric(),
                Forms\Components\TextInput::make('currency')->label($l('العملة', 'Currency'))->default('EGP'),
                Forms\Components\Textarea::make('payment_terms')->label($l('شروط الدفع', 'Payment Terms')),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label($l('المندوب', 'Rep')),
                Tables\Columns\TextColumn::make('supplier.name')->label($l('المورد', 'Supplier'))
                    ->formatStateUsing(fn ($s, $r) => $r->supplier?->name ?? $l('غير محدد', 'N/A')),
                Tables\Columns\TextColumn::make('product.name_ar')->label($l('المنتج', 'Product'))->searchable(),
                Tables\Columns\TextColumn::make('quantity')->label($l('الكمية', 'Qty')),
                Tables\Columns\TextColumn::make('offered_price')->label($l('السعر', 'Price')),
                Tables\Columns\BadgeColumn::make('status')->label($l('الحالة', 'Status'))
                    ->colors([
                        'pending' => 'warning',
                        'sales_approved' => 'info',
                        'purchasing_approved' => 'success',
                        'rejected_by_sales' => 'danger',
                        'rejected_by_purchasing' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label($l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label($l('الحالة', 'Status'))
                    ->options([
                        'pending' => $l('معلق', 'Pending'),
                        'sales_approved' => $l('Sales وافق', 'Sales approved'),
                        'purchasing_approved' => $l('Purchasing وافق', 'Purchasing approved'),
                        'rejected_by_sales' => $l('Sales رفض', 'Rejected by Sales'),
                        'rejected_by_purchasing' => $l('Purchasing رفض', 'Rejected by Purchasing'),
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('sales_approve')
                    ->label($l('موافقة Sales', 'Sales Approve'))
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (PurchaseRequest $r) => $r->status === 'pending' && auth()->user()->hasAnyRole(['admin', 'sales_manager']))
                    ->action(function (PurchaseRequest $r) {
                        $r->update([
                            'status' => 'sales_approved',
                            'sales_reviewed_by' => Auth::id(),
                            'sales_reviewed_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation(),
                Action::make('sales_reject')
                    ->label($l('رفض Sales', 'Sales Reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (PurchaseRequest $r) => $r->status === 'pending' && auth()->user()->hasAnyRole(['admin', 'sales_manager']))
                    ->action(function (PurchaseRequest $r) {
                        $r->update([
                            'status' => 'rejected_by_sales',
                            'sales_reviewed_by' => Auth::id(),
                            'sales_reviewed_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation(),
                Action::make('purchasing_approve')
                    ->label($l('موافقة Purchasing', 'Purchasing Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PurchaseRequest $r) => $r->status === 'sales_approved' && auth()->user()->hasAnyRole(['admin', 'purchasing']))
                    ->action(function (PurchaseRequest $r) {
                        $r->update([
                            'status' => 'purchasing_approved',
                            'purchasing_reviewed_by' => Auth::id(),
                            'purchasing_reviewed_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation(),
                Action::make('purchasing_reject')
                    ->label($l('رفض Purchasing', 'Purchasing Reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PurchaseRequest $r) => $r->status === 'sales_approved' && auth()->user()->hasAnyRole(['admin', 'purchasing']))
                    ->action(function (PurchaseRequest $r) {
                        $r->update([
                            'status' => 'rejected_by_purchasing',
                            'purchasing_reviewed_by' => Auth::id(),
                            'purchasing_reviewed_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation(),
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseRequests::route('/'),
            'create' => Pages\CreatePurchaseRequest::route('/create'),
            'edit' => Pages\EditPurchaseRequest::route('/{record}/edit'),
        ];
    }
}
