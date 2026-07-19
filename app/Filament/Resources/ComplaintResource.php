<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComplaintResource\Pages;
use App\Models\Complaint;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ComplaintResource extends Resource
{
    public static function getModel(): string
    {
        return Complaint::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chat-bubble-left-ellipsis';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'التنبيهات' : 'Alarms';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'شكوى' : 'Complaint';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الشكاوى' : 'Complaints';
    }

    public static function form(Schema $schema): Schema
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $schema->schema([
            Forms\Components\Section::make($l('الشكوى', 'Complaint'))->schema([
                Forms\Components\Select::make('customer_id')->label($l('العميل', 'Customer'))
                    ->relationship('customer', 'name_ar')->preload()->required(),
                Forms\Components\Select::make('user_id')->label($l('المندوب', 'Rep'))
                    ->relationship('user', 'name')->preload()->required(),
                Forms\Components\Select::make('complaint_type')->label($l('النوع', 'Type'))
                    ->options([
                        'non_conforming_materials' => $l('مواد غير مطابقة', 'Non-conforming'),
                        'delivery_issue' => $l('مشكلة تسليم', 'Delivery'),
                        'quality_issue' => $l('مشكلة جودة', 'Quality'),
                        'pricing_issue' => $l('مشكلة تسعير', 'Pricing'),
                        'other' => $l('أخرى', 'Other'),
                    ]),
                Forms\Components\Textarea::make('description')->label($l('الوصف', 'Description'))->required(),
                Forms\Components\Select::make('status')->label($l('الحالة', 'Status'))
                    ->options([
                        'open' => $l('مفتوحة', 'Open'),
                        'in_progress' => $l('قيد المعالجة', 'In Progress'),
                        'resolved' => $l('محلولة', 'Resolved'),
                        'closed' => $l('مغلقة', 'Closed'),
                    ]),
                Forms\Components\Textarea::make('resolution')->label($l('الحل', 'Resolution')),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name_ar')->label($l('العميل', 'Customer'))->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label($l('المندوب', 'Rep')),
                Tables\Columns\BadgeColumn::make('complaint_type')->label($l('النوع', 'Type'))
                    ->colors(['pricing_issue' => 'warning', 'quality_issue' => 'danger', 'delivery_issue' => 'info']),
                Tables\Columns\BadgeColumn::make('status')->label($l('الحالة', 'Status'))
                    ->colors(['open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'gray']),
                Tables\Columns\TextColumn::make('created_at')->label($l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label($l('الحالة', 'Status'))
                    ->options(['open' => $l('مفتوحة', 'Open'), 'resolved' => $l('محلولة', 'Resolved'), 'closed' => $l('مغلقة', 'Closed')]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComplaints::route('/'),
            'create' => Pages\CreateComplaint::route('/create'),
            'edit' => Pages\EditComplaint::route('/{record}/edit'),
        ];
    }
}
