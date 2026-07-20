<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyVisitAssignmentResource\Pages;
use App\Models\DailyVisitAssignment;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DailyVisitAssignmentResource extends Resource
{
    public static function getModel(): string
    {
        return DailyVisitAssignment::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'تعيين زيارة' : 'Visit Assignment';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'تعيينات الزيارات' : 'Visit Assignments';
    }

    public static function form(Schema $schema): Schema
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $schema->schema([
            Section::make($l('بيانات', 'Info'))->schema([
                Forms\Components\Select::make('user_id')
                    ->label($l('المندوب', 'Rep'))
                    ->relationship('user', 'name')
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->label($l('العميل', 'Customer'))
                    ->relationship('customer', 'name_ar')
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('visit_date')
                    ->label($l('تاريخ الزيارة', 'Visit Date'))
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->label($l('الترتيب', 'Sort Order'))
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('status')
                    ->label($l('الحالة', 'Status'))
                    ->options([
                        'pending' => $l('معلق', 'Pending'),
                        'completed' => $l('مكتمل', 'Completed'),
                        'missed' => $l('فاتت', 'Missed'),
                    ])
                    ->default('pending'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label($l('المندوب', 'Rep'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name_ar')->label($l('العميل', 'Customer'))->searchable(),
                Tables\Columns\TextColumn::make('visit_date')->label($l('التاريخ', 'Date'))->date()->sortable(),
                Tables\Columns\BadgeColumn::make('status')->label($l('الحالة', 'Status'))
                    ->colors(['pending' => 'warning', 'completed' => 'success', 'missed' => 'danger']),
                Tables\Columns\TextColumn::make('sort_order')->label($l('الترتيب', 'Order'))->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')->label($l('المندوب', 'Rep'))->relationship('user', 'name'),
                Tables\Filters\SelectFilter::make('status')->label($l('الحالة', 'Status'))
                    ->options(['pending' => $l('معلق', 'Pending'), 'completed' => $l('مكتمل', 'Completed'), 'missed' => $l('فاتت', 'Missed')]),
            ])
            ->defaultSort('visit_date', 'desc')
            ->actions([EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyVisitAssignments::route('/'),
            'create' => Pages\CreateDailyVisitAssignment::route('/create'),
            'edit' => Pages\EditDailyVisitAssignment::route('/{record}/edit'),
        ];
    }
}
