<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CallResource\Pages;
use App\Models\Call;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CallResource extends Resource
{
    public static function getModel(): string
    {
        return Call::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-phone';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المكالمات' : 'Calls';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'مكالمة' : 'Call';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'المكالمات' : 'Calls';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(l('بيانات المكالمة', 'Call Details'))->schema([
                Forms\Components\Select::make('customer_id')->label(l('العميل', 'Customer'))
                    ->relationship('customer', 'name_ar')->preload()->required(),
                Forms\Components\Select::make('user_id')->label(l('المستخدم', 'User'))
                    ->relationship('user', 'name')->preload()->required(),
                Forms\Components\TextInput::make('duration_seconds')->label(l('المدة بالثواني', 'Duration (seconds)'))->numeric()->default(0),
                Forms\Components\Textarea::make('notes')->label(l('ملاحظات', 'Notes')),
                Forms\Components\DateTimePicker::make('call_date')->label(l('تاريخ المكالمة', 'Call Date'))->default(now()),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name_ar')->label(l('العميل', 'Customer'))->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label(l('المستخدم', 'User')),
                Tables\Columns\TextColumn::make('duration_seconds')->label(l('المدة', 'Duration'))
                    ->formatStateUsing(fn ($state) => sprintf('%d:%02d', intdiv((int) $state, 60), $state % 60)),
                Tables\Columns\TextColumn::make('notes')->label(l('ملاحظات', 'Notes'))->limit(50)->placeholder('—'),
                Tables\Columns\TextColumn::make('call_date')->label(l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->defaultSort('call_date', 'desc')
            ->actions([EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalls::route('/'),
            'create' => Pages\CreateCall::route('/create'),
            'edit' => Pages\EditCall::route('/{record}/edit'),
        ];
    }
}
