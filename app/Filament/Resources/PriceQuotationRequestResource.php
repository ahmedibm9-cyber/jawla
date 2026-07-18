<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceQuotationRequestResource\Pages;
use App\Models\PriceQuotation;
use App\Models\PriceQuotationRequest;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PriceQuotationRequestResource extends Resource
{
    public static function getModel(): string
    {
        return PriceQuotationRequest::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'طلب عرض سعر' : 'Quotation Request';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'عروض الأسعار' : 'Quotations';
    }

    public static function form(Schema $schema): Schema
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $schema->schema([
            Forms\Components\Section::make($l('طلب عرض سعر', 'Quotation Request'))->schema([
                Forms\Components\Select::make('customer_id')->label($l('العميل', 'Customer'))
                    ->relationship('customer', 'name_ar')->preload()->required(),
                Forms\Components\Select::make('product_id')->label($l('المنتج', 'Product'))
                    ->relationship('product', 'name_ar')->preload()->required(),
                Forms\Components\Select::make('user_id')->label($l('المندوب', 'Rep'))
                    ->relationship('user', 'name')->preload()->required(),
                Forms\Components\TextInput::make('quantity_requested')->label($l('الكمية المطلوبة', 'Qty'))
                    ->numeric()->required()->minValue(0),
                Forms\Components\Select::make('status')->label($l('الحالة', 'Status'))
                    ->options(['requested' => 'Requested', 'priced' => 'Priced', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'])
                    ->default('requested'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name_ar')->label(app()->getLocale() === 'ar' ? 'المنتج' : 'Product')->searchable(),
                Tables\Columns\TextColumn::make('customer.name_ar')->label(app()->getLocale() === 'ar' ? 'العميل' : 'Customer'),
                Tables\Columns\TextColumn::make('user.name')->label(app()->getLocale() === 'ar' ? 'المندوب' : 'Rep'),
                Tables\Columns\TextColumn::make('quantity_requested')->label(app()->getLocale() === 'ar' ? 'الكمية' : 'Qty'),
                Tables\Columns\BadgeColumn::make('status')->label(app()->getLocale() === 'ar' ? 'الحالة' : 'Status')
                    ->colors(['requested' => 'warning', 'priced' => 'info', 'confirmed' => 'success', 'cancelled' => 'danger']),
                Tables\Columns\TextColumn::make('requested_at')->label(app()->getLocale() === 'ar' ? 'تاريخ الطلب' : 'Date')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['requested' => 'Requested', 'priced' => 'Priced', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled']),
            ])
            ->actions([
                Filament\Actions\Action::make('set_price')
                    ->label(app()->getLocale() === 'ar' ? 'تسعير' : 'Set Price')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (PriceQuotationRequest $r) => $r->status === 'requested' && auth()->user()->hasAnyRole(['admin', 'accounts']))
                    ->form([
                        Forms\Components\TextInput::make('base_price')->label(app()->getLocale() === 'ar' ? 'السعر الأساسي' : 'Base Price')->numeric()->required(),
                        Forms\Components\TextInput::make('manager_plus')->label(app()->getLocale() === 'ar' ? 'الحد الأعلى (±)' : 'Plus Range')->numeric()->default(0),
                        Forms\Components\TextInput::make('manager_minus')->label(app()->getLocale() === 'ar' ? 'الحد الأدنى (±)' : 'Minus Range')->numeric()->default(0),
                        Forms\Components\TextInput::make('rep_plus')->label(app()->getLocale() === 'ar' ? 'نطاق المندوب (+)' : 'Rep Plus')->numeric()->default(0),
                        Forms\Components\TextInput::make('rep_minus')->label(app()->getLocale() === 'ar' ? 'نطاق المندوب (-)' : 'Rep Minus')->numeric()->default(0),
                    ])
                    ->action(function (PriceQuotationRequest $r, array $data): void {
                        PriceQuotation::create([
                            'price_quotation_request_id' => $r->id,
                            'base_price' => $data['base_price'],
                            'manager_plus' => $data['manager_plus'],
                            'manager_minus' => $data['manager_minus'],
                            'rep_plus' => $data['rep_plus'],
                            'rep_minus' => $data['rep_minus'],
                            'priced_by' => auth()->id(),
                            'priced_at' => now(),
                        ]);
                        $r->update(['status' => 'priced']);
                    }),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotationRequests::route('/'),
            'create' => Pages\CreateQuotationRequest::route('/create'),
            'edit' => Pages\EditQuotationRequest::route('/{record}/edit'),
        ];
    }
}
