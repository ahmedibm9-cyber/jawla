<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    public static function getModel(): string
    {
        return Company::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-building-office-2';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'الشركة' : 'Company';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'شركة' : 'Company';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الشركات' : 'Companies';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make(app()->getLocale() === 'ar' ? 'البيانات الأساسية' : 'Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label(app()->getLocale() === 'ar' ? 'الاسم (عربي)' : 'Name (Arabic)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name_en')
                            ->label(app()->getLocale() === 'ar' ? 'الاسم (إنجليزي)' : 'Name (English)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('abbr')
                            ->label(app()->getLocale() === 'ar' ? 'الاختصار' : 'Abbreviation')
                            ->maxLength(10),
                        Forms\Components\TextInput::make('legal_entity')
                            ->label(app()->getLocale() === 'ar' ? 'الكيان القانوني' : 'Legal Entity')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('parent_company')
                            ->label(app()->getLocale() === 'ar' ? 'الشركة الأم' : 'Parent Company')
                            ->default('Global Plastic Company (GPC)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tax_number')
                            ->label(app()->getLocale() === 'ar' ? 'الرقم الضريبي' : 'Tax Number')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('commercial_registration_number')
                            ->label(app()->getLocale() === 'ar' ? 'السجل التجاري' : 'Commercial Registration')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label(app()->getLocale() === 'ar' ? 'الهاتف' : 'Phone')
                            ->tel(),
                        Forms\Components\TextInput::make('geofence_radius_m')
                            ->label(app()->getLocale() === 'ar' ? 'نطاق تأكيد الزيارة (متر)' : 'Visit Geofence Radius (m)')
                            ->helperText(app()->getLocale() === 'ar'
                                ? 'المسافة القصوى المسموح بها لتأكيد وصول المندوب'
                                : 'Maximum distance allowed for rep arrival confirmation')
                            ->numeric()
                            ->integer()
                            ->minValue(50)
                            ->maxValue(5000)
                            ->default(500)
                            ->required()
                            ->suffix('m'),
                        Forms\Components\Textarea::make('address')
                            ->label(app()->getLocale() === 'ar' ? 'العنوان' : 'Address')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(app()->getLocale() === 'ar' ? 'الإعدادات المالية' : 'Financial Settings')
                    ->schema([
                        Forms\Components\TextInput::make('currency')
                            ->label(app()->getLocale() === 'ar' ? 'العملة' : 'Currency')
                            ->default('EGP')
                            ->required(),
                        Forms\Components\TextInput::make('vat_percent')
                            ->label(app()->getLocale() === 'ar' ? 'نسبة الضريبة %' : 'VAT %')
                            ->numeric()
                            ->default(14.00)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(app()->getLocale() === 'ar' ? 'الحساب البنكي' : 'Bank Account')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')->label(app()->getLocale() === 'ar' ? 'اسم البنك' : 'Bank Name'),
                        Forms\Components\TextInput::make('bank_account')->label(app()->getLocale() === 'ar' ? 'رقم الحساب' : 'Account Number'),
                        Forms\Components\TextInput::make('bank_iban')->label('IBAN'),
                    ])
                    ->columns(3),

                Forms\Components\Toggle::make('is_active')
                    ->label(app()->getLocale() === 'ar' ? 'نشط' : 'Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label(app()->getLocale() === 'ar' ? 'الاسم' : 'Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('abbr')
                    ->label(app()->getLocale() === 'ar' ? 'اختصار' : 'Abbr')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_number')
                    ->label(app()->getLocale() === 'ar' ? 'الرقم الضريبي' : 'Tax Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency')
                    ->label(app()->getLocale() === 'ar' ? 'العملة' : 'Currency'),
                Tables\Columns\TextColumn::make('vat_percent')
                    ->label(app()->getLocale() === 'ar' ? 'الضريبة %' : 'VAT %')
                    ->suffix('%'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(app()->getLocale() === 'ar' ? 'نشط' : 'Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(app()->getLocale() === 'ar' ? 'تاريخ الإنشاء' : 'Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(app()->getLocale() === 'ar' ? 'نشط' : 'Active'),
            ])
            ->actions([
                EditAction::make(),
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
