<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    public static function getModel(): string
    {
        return Ticket::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-ticket';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'التذاكر' : 'Tickets';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'تذكرة' : 'Ticket';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'التذاكر' : 'Tickets';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(l('بيانات التذكرة', 'Ticket Details'))->schema([
                Forms\Components\TextInput::make('title')->label(l('العنوان', 'Title'))->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->label(l('الوصف', 'Description'))->required(),
                Forms\Components\Select::make('status')->label(l('الحالة', 'Status'))
                    ->options([
                        'new' => l('جديدة', 'New'),
                        'in_progress' => l('قيد المعالجة', 'In Progress'),
                        'completed' => l('مكتملة', 'Completed'),
                        'cancelled' => l('ملغاة', 'Cancelled'),
                    ])->default('new'),
                Forms\Components\Select::make('priority')->label(l('الأولوية', 'Priority'))
                    ->options([
                        'low' => l('منخفضة', 'Low'),
                        'medium' => l('متوسطة', 'Medium'),
                        'high' => l('عالية', 'High'),
                    ])->default('medium'),
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
                Tables\Columns\TextColumn::make('customer.name_ar')->label(l('العميل', 'Customer'))->placeholder('—'),
                Tables\Columns\BadgeColumn::make('status')->label(l('الحالة', 'Status'))
                    ->colors([
                        'new' => 'info',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'gray',
                    ]),
                Tables\Columns\BadgeColumn::make('priority')->label(l('الأولوية', 'Priority'))
                    ->colors(['low' => 'gray', 'medium' => 'warning', 'high' => 'danger']),
                Tables\Columns\TextColumn::make('created_at')->label(l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(l('الحالة', 'Status'))
                    ->options([
                        'new' => l('جديدة', 'New'),
                        'in_progress' => l('قيد المعالجة', 'In Progress'),
                        'completed' => l('مكتملة', 'Completed'),
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
