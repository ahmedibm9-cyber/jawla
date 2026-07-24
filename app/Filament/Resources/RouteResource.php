<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RouteResource\Pages;
use App\Models\Route;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RouteResource extends Resource
{
    public static function getModel(): string
    {
        return Route::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-map';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'خط سير' : 'Route';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'خطوط السير' : 'Routes';
    }

    public static function form(Schema $schema): Schema
    {

        return $schema->schema([
            Forms\Components\TextInput::make('name_ar')->label(l('الاسم (عربي)', 'Name (Arabic)'))->required(),
            Forms\Components\TextInput::make('name_en')->label(l('الاسم (إنجليزي)', 'Name (English)'))->required(),
            Forms\Components\TextInput::make('region')->label(l('المنطقة', 'Region')),
            Forms\Components\Select::make('users')->label(l('المندوبين', 'Reps'))->relationship('users', 'name')->multiple()->preload(),
            Forms\Components\Toggle::make('is_active')->label(l('نشط', 'Active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')->label(l('الاسم', 'Name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('region')->label(l('المنطقة', 'Region')),
                Tables\Columns\TextColumn::make('users.name')->label(l('المندوبين', 'Reps'))->formatStateUsing(fn ($s) => collect($s)->join(', ')),
                Tables\Columns\IconColumn::make('is_active')->label(l('نشط', 'Active'))->boolean(),
            ])
            ->filters([Tables\Filters\TernaryFilter::make('is_active')])
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
            'index' => Pages\ListRoutes::route('/'),
            'create' => Pages\CreateRoute::route('/create'),
            'edit' => Pages\EditRoute::route('/{record}/edit'),
        ];
    }
}
