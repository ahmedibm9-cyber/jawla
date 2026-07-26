<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductPriceResource\Pages;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductPrice;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductPriceResource extends Resource
{
    protected static ?string $model = ProductPrice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    public static function getNavigationGroup(): ?string
    {
        return l('المبيعات', 'Sales');
    }

    public static function getLabel(): string
    {
        return l('استثناء سعر عميل', 'Customer price override');
    }

    public static function getPluralLabel(): string
    {
        return l('استثناءات أسعار العملاء', 'Customer price overrides');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('sales_manager') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $companyId = auth()->user()?->activeCompanyId();

        return $schema->schema([
            Forms\Components\Select::make('customer_id')
                ->label(l('العميل', 'Customer'))
                ->options(Customer::where('company_id', $companyId)->pluck('name', 'id'))
                ->searchable()
                ->required(),
            Forms\Components\Select::make('product_id')
                ->label(l('المنتج', 'Product'))
                ->options(Product::where('company_id', $companyId)->pluck('name_ar', 'id'))
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('price')
                ->label(l('السعر المعتمد', 'Approved price'))
                ->numeric()
                ->minValue(0.01)
                ->required(),
            Forms\Components\DatePicker::make('valid_from')
                ->label(l('ساري من', 'Valid from'))
                ->required(),
            Forms\Components\DatePicker::make('valid_upto')
                ->label(l('ساري حتى', 'Valid until'))
                ->afterOrEqual('valid_from')
                ->required(),
            Forms\Components\Textarea::make('reason')
                ->label(l('سبب الاستثناء', 'Override reason'))
                ->required()
                ->maxLength(1000)
                ->columnSpanFull(),
            Forms\Components\Toggle::make('confirmed')
                ->label(l(
                    'أؤكد أن هذا السعر سيحل محل السعر القياسي لهذا العميل والمنتج خلال المدة المحددة.',
                    'I confirm this price will replace the standard price for this customer and product during the selected period.',
                ))
                ->accepted()
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')->label(l('العميل', 'Customer')),
                Tables\Columns\TextColumn::make('product.name_ar')->label(l('المنتج', 'Product')),
                Tables\Columns\TextColumn::make('price')->label(l('السعر', 'Price'))->money('EGP'),
                Tables\Columns\TextColumn::make('valid_from')->label(l('من', 'From'))->date(),
                Tables\Columns\TextColumn::make('valid_upto')->label(l('حتى', 'Until'))->date(),
                Tables\Columns\TextColumn::make('reason')->label(l('السبب', 'Reason'))->limit(50),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $companyId = auth()->user()?->activeCompanyId();

        return parent::getEloquentQuery()
            ->where('is_customer_override', true)
            ->whereHas('priceList', fn (Builder $query) => $query->where('company_id', $companyId));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductPrices::route('/'),
            'create' => Pages\CreateProductPrice::route('/create'),
        ];
    }
}
