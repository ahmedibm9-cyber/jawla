<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class ProductResource extends Resource
{
    public static function getModel(): string
    {
        return Product::class;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any:product') ?? false;
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
        return app()->getLocale() === 'ar' ? 'منتج' : 'Product';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'المنتجات' : 'Products';
    }

    public static function form(Schema $schema): Schema
    {

        return $schema->schema([
            Section::make(l('البيانات الأساسية', 'Basic Information'))->schema([
                Forms\Components\TextInput::make('sku')->label('SKU')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('barcode')->label(l('الباركود', 'Barcode'))
                    ->helperText(l('امسح أو أدخل باركود المنتج للبيع السريع', 'Scan or enter the product barcode for quick sales'))
                    ->maxLength(64),
                Forms\Components\TextInput::make('name_ar')->label(l('الاسم (عربي)', 'Name (Arabic)'))->required(),
                Forms\Components\TextInput::make('name_en')->label(l('الاسم (إنجليزي)', 'Name (English)'))->required(),
                Forms\Components\Select::make('category_id')->label(l('الفئة', 'Category'))->relationship('category', 'name_ar')->preload()->required(),
                Forms\Components\Select::make('unit')->label(l('الوحدة', 'Unit'))
                    ->options(['ton' => l('طن', 'Ton'), 'kg' => l('كجم', 'Kg'), 'piece' => l('قطعة', 'Piece'), 'box' => l('صندوق', 'Box'), 'carton' => l('كرتون', 'Carton')])
                    ->default('ton')->required(),
                Forms\Components\Select::make('packaging_type')->label(l('نوع التعبئة', 'Packaging'))
                    ->options(['bag' => l('كيس', 'Bag'), 'jumbo_bag' => l('جمبو', 'Jumbo Bag'), 'barrel' => l('برميل', 'Barrel'), 'drum' => l('درام', 'Drum'), 'tank' => l('تانك', 'Tank'), 'iso_tank' => 'ISO Tank', 'other' => l('أخرى', 'Other')])
                    ->default('bag'),
                Forms\Components\Toggle::make('track_batch')->label(l('تتبع التشغيلة', 'Track Batch')),
                Forms\Components\Toggle::make('track_expiry')->label(l('تتبع الصلاحية', 'Track Expiry')),
                Forms\Components\Toggle::make('vat_applicable')->label(l('خاضع للضريبة', 'VAT Applicable'))->default(true),
            ])->columns(3),

            Section::make(l('التسعير', 'Pricing'))->schema([
                Forms\Components\TextInput::make('price')->label(l('سعر البيع', 'Selling Price'))->numeric()->required()
                    ->visible(fn () => Gate::allows('products.manage_prices')),
                Forms\Components\TextInput::make('cost')->label(l('سعر التكلفة', 'Cost Price'))->numeric()->required()
                    ->visible(fn () => Gate::allows('products.view_cost')),
                Forms\Components\TextInput::make('max_discount')->label(l('الحد الأقصى للخصم', 'Max Discount'))->numeric(),
                Forms\Components\Select::make('valuation_method')->label(l('طريقة التقييم', 'Valuation'))
                    ->options(['fifo' => 'FIFO', 'moving_average' => l('المتوسط المتحرك', 'Moving Average'), 'standard' => l('قياسي', 'Standard')])
                    ->default('moving_average'),
            ])->columns(3),

            Forms\Components\Toggle::make('is_active')->label(l('نشط', 'Active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')->label('SKU')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name_ar')->label(l('الاسم', 'Name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name_ar')->label(l('الفئة', 'Category')),
                Tables\Columns\TextColumn::make('unit')->label(l('الوحدة', 'Unit')),
                Tables\Columns\TextColumn::make('price')->label(l('السعر', 'Price'))->money('EGP')->sortable()
                    ->visible(fn () => Gate::allows('products.view_cost')),
                Tables\Columns\TextColumn::make('cost')->label(l('التكلفة', 'Cost'))->money('EGP')->sortable()
                    ->visible(fn () => Gate::allows('products.view_cost')),
                Tables\Columns\IconColumn::make('vat_applicable')->label(l('ضريبة', 'VAT'))->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label(l('نشط', 'Active'))->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')->label(l('الفئة', 'Category'))->relationship('category', 'name_ar'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
