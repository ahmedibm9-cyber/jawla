<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationUnitResource\Pages;
use App\Models\OrganizationUnit;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrganizationUnitResource extends Resource
{
    protected static ?string $model = OrganizationUnit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    public static function getNavigationGroup(): ?string
    {
        return l('الشركة', 'Company');
    }

    public static function getLabel(): string
    {
        return l('وحدة تنظيمية', 'Organization unit');
    }

    public static function getPluralLabel(): string
    {
        return l('الهيكل التنظيمي', 'Organization structure');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(l('بيانات الوحدة', 'Unit details'))->schema([
                Forms\Components\Select::make('type')->label(l('النوع', 'Type'))->options([
                    'region' => l('إقليم', 'Region'),
                    'branch' => l('فرع', 'Branch'),
                    'area' => l('منطقة', 'Area'),
                    'team' => l('فريق', 'Team'),
                ])->required(),
                Forms\Components\TextInput::make('code')->label(l('الكود', 'Code'))->required()->maxLength(50),
                Forms\Components\TextInput::make('name_ar')->label(l('الاسم بالعربية', 'Arabic name'))->required()->maxLength(255),
                Forms\Components\TextInput::make('name_en')->label(l('الاسم بالإنجليزية', 'English name'))->maxLength(255),
                Forms\Components\Select::make('parent_id')->label(l('الوحدة الأعلى', 'Parent unit'))
                    ->relationship('parent', 'name_ar', fn ($query, ?OrganizationUnit $record) => $record ? $query->whereKeyNot($record->getKey()) : $query)
                    ->searchable()->preload(),
                Forms\Components\Select::make('manager_id')->label(l('المدير', 'Manager'))
                    ->relationship('manager', 'name')->searchable()->preload(),
                Forms\Components\Toggle::make('is_active')->label(l('نشطة', 'Active'))->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->label(l('الكود', 'Code'))->searchable()->sortable(),
            Tables\Columns\TextColumn::make('name_ar')->label(l('الاسم', 'Name'))->searchable(),
            Tables\Columns\TextColumn::make('type')->label(l('النوع', 'Type'))->badge(),
            Tables\Columns\TextColumn::make('parent.name_ar')->label(l('الوحدة الأعلى', 'Parent')),
            Tables\Columns\TextColumn::make('manager.name')->label(l('المدير', 'Manager')),
            Tables\Columns\IconColumn::make('is_active')->label(l('نشطة', 'Active'))->boolean(),
        ])->filters([
            Tables\Filters\SelectFilter::make('type')->options([
                'region' => 'Region', 'branch' => 'Branch', 'area' => 'Area', 'team' => 'Team',
            ]),
        ])->actions([EditAction::make()])->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationUnits::route('/'),
            'create' => Pages\CreateOrganizationUnit::route('/create'),
            'edit' => Pages\EditOrganizationUnit::route('/{record}/edit'),
        ];
    }
}
