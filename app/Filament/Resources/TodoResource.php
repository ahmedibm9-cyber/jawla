<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TodoResource\Pages;
use App\Models\Todo;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TodoResource extends Resource
{
    public static function getModel(): string
    {
        return Todo::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-check-circle';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المهام' : 'Tasks';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'مهمة' : 'Todo';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'المهام' : 'Todos';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(l('بيانات المهمة', 'Todo Details'))->schema([
                Forms\Components\TextInput::make('title')->label(l('العنوان', 'Title'))->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->label(l('الوصف', 'Description')),
                Forms\Components\Select::make('priority')->label(l('الأولوية', 'Priority'))
                    ->options([
                        'low' => l('منخفضة', 'Low'),
                        'medium' => l('متوسطة', 'Medium'),
                        'high' => l('عالية', 'High'),
                    ])->default('medium')->required(),
                Forms\Components\DatePicker::make('due_date')->label(l('تاريخ الاستحقاق', 'Due Date'))->required(),
                Forms\Components\Select::make('status')->label(l('الحالة', 'Status'))
                    ->options([
                        'new' => l('جديد', 'New'),
                        'done' => l('مكتمل', 'Done'),
                    ])->default('new'),
                Forms\Components\Toggle::make('is_active')->label(l('نشط', 'Active'))->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label(l('العنوان', 'Title'))->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label(l('المستخدم', 'User')),
                Tables\Columns\BadgeColumn::make('priority')->label(l('الأولوية', 'Priority'))
                    ->colors(['low' => 'gray', 'medium' => 'warning', 'high' => 'danger']),
                Tables\Columns\BadgeColumn::make('status')->label(l('الحالة', 'Status'))
                    ->colors(['new' => 'info', 'done' => 'success']),
                Tables\Columns\TextColumn::make('due_date')->label(l('الاستحقاق', 'Due'))->date()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label(l('نشط', 'Active'))->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(l('الحالة', 'Status'))
                    ->options(['new' => l('جديد', 'New'), 'done' => l('مكتمل', 'Done')]),
                Tables\Filters\SelectFilter::make('priority')->label(l('الأولوية', 'Priority'))
                    ->options(['low' => l('منخفضة', 'Low'), 'medium' => l('متوسطة', 'Medium'), 'high' => l('عالية', 'High')]),
            ])
            ->defaultSort('due_date', 'asc')
            ->actions([EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTodos::route('/'),
            'create' => Pages\CreateTodo::route('/create'),
            'edit' => Pages\EditTodo::route('/{record}/edit'),
        ];
    }
}
