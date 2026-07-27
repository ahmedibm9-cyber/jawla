<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    public static function getModel(): string
    {
        return Task::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-check-circle';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'إدارة' : 'Management';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'مهمة' : 'Task';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'المهام' : 'Tasks';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create:task') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update:task') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete:task') ?? false;
    }

    public static function form(Schema $schema): Schema
    {

        return $schema->schema([
            Section::make(l('تفاصيل المهمة', 'Task Details'))->schema([
                Forms\Components\Select::make('assigned_to')
                    ->label(l('مسند إلى', 'Assigned To'))
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->required()
                    ->helperText(l('اختر الشخص المسؤول عن تنفيذ المهمة', 'Select the person responsible for the task')),
                Forms\Components\Select::make('customer_id')
                    ->label(l('العميل', 'Customer'))
                    ->relationship('customer', 'name_ar')
                    ->searchable()
                    ->nullable()
                    ->helperText(l('العميل المعني بالمهمة (اختياري)', 'Customer related to the task (optional)')),
                Forms\Components\TextInput::make('title')
                    ->label(l('العنوان', 'Title'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('note')
                    ->label(l('ملاحظات', 'Note'))
                    ->nullable(),
                Forms\Components\DatePicker::make('due_date')
                    ->label(l('تاريخ الاستحقاق', 'Due Date'))
                    ->nullable()
                    ->helperText(l('التاريخ المطلوب لإنجاز المهمة', 'The deadline for completing the task')),
                Forms\Components\Select::make('status')
                    ->label(l('الحالة', 'Status'))
                    ->options(['open' => l('مفتوحة', 'Open'), 'done' => l('تم', 'Done')])
                    ->default('open')
                    ->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                TextColumn::make('title')->label(l('العنوان', 'Title'))->searchable(),
                TextColumn::make('assignedTo.name')->label(l('مسند إلى', 'Assigned To')),
                TextColumn::make('status')
                    ->label(l('الحالة', 'Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'done' => 'success', default => 'warning'
                    }),
                TextColumn::make('due_date')->label(l('تاريخ الاستحقاق', 'Due Date'))->date(),
                TextColumn::make('created_at')->label(l('تاريخ الإنشاء', 'Created'))->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['open' => l('مفتوحة', 'Open'), 'done' => l('تم', 'Done')]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
