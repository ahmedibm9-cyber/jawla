<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchResource\Pages;
use App\Models\Batch;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BatchResource extends Resource
{
    public static function getModel(): string
    {
        return Batch::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-beaker';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المخزون' : 'Inventory';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'دفعة' : 'Batch';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الدفعات' : 'Batches';
    }

    // Batches have no company_id; scope through the owning product.
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('product', fn ($q) => $q->where('company_id', Auth::user()?->company_id));
    }

    public static function form(Schema $schema): Schema
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;
        $companyId = Auth::user()?->company_id;

        return $schema->schema([
            Section::make($l('بيانات الدفعة', 'Batch Details'))->schema([
                Forms\Components\Select::make('product_id')->label($l('المنتج', 'Product'))
                    ->options(fn () => Product::where('company_id', $companyId)->pluck('name_ar', 'id'))
                    ->searchable()->required(),
                Forms\Components\TextInput::make('batch_number')->label($l('رقم الدفعة', 'Batch Number'))->required()->maxLength(100),
                Forms\Components\DatePicker::make('manufacture_date')->label($l('تاريخ الإنتاج', 'Manufacture Date')),
                Forms\Components\DatePicker::make('expiry_date')->label($l('تاريخ الانتهاء', 'Expiry Date')),
                Forms\Components\DatePicker::make('received_date')->label($l('تاريخ الاستلام', 'Received Date'))->required()->default(now()),
                Forms\Components\Select::make('supplier_id')->label($l('المورد', 'Supplier'))
                    ->relationship('supplier', 'name_ar')->searchable(),
                Forms\Components\FileUpload::make('coa_file_path')->label($l('شهادة التحليل (COA)', 'Certificate of Analysis'))
                    ->directory('coa')->acceptedFileTypes(['application/pdf', 'image/*']),
                Forms\Components\Toggle::make('is_active')->label($l('نشط', 'Active'))->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name_ar')->label($l('المنتج', 'Product'))->searchable(),
                Tables\Columns\TextColumn::make('batch_number')->label($l('رقم الدفعة', 'Batch #'))->searchable(),
                Tables\Columns\TextColumn::make('expiry_date')->label($l('الانتهاء', 'Expiry'))->date()
                    ->color(fn (Batch $r) => $r->isExpired() ? 'danger' : ($r->expiresWithinDays(30) ? 'warning' : 'success'))
                    ->description(fn (Batch $r) => $r->isExpired() ? $l('منتهي', 'Expired') : ($r->expiresWithinDays(30) ? $l('قريب الانتهاء', 'Expiring soon') : null)),
                Tables\Columns\TextColumn::make('supplier.name_ar')->label($l('المورد', 'Supplier'))->placeholder('—'),
                Tables\Columns\IconColumn::make('coa_file_path')->label($l('COA', 'COA'))->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label($l('نشط', 'Active'))->boolean(),
            ])
            ->filters([
                Tables\Filters\Filter::make('expired')->label($l('منتهية', 'Expired'))
                    ->query(fn (Builder $q) => $q->whereNotNull('expiry_date')->whereDate('expiry_date', '<', today())),
                Tables\Filters\Filter::make('expiring_soon')->label($l('قريبة الانتهاء', 'Expiring soon'))
                    ->query(fn (Builder $q) => $q->expiringWithin(30)),
                Tables\Filters\TernaryFilter::make('is_active')->label($l('نشطة', 'Active')),
            ])
            ->defaultSort('expiry_date', 'asc')
            ->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatches::route('/'),
            'create' => Pages\CreateBatch::route('/create'),
            'edit' => Pages\EditBatch::route('/{record}/edit'),
        ];
    }
}
