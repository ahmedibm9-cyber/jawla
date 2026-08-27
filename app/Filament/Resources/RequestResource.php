<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestResource\Pages;
use App\Models\Request;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RequestResource extends Resource
{
    public static function getModel(): string
    {
        return Request::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-paper-airplane';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'الطلبات' : 'Requests';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'طلب' : 'Request';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الطلبات' : 'Requests';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(l('بيانات الطلب', 'Request Details'))->schema([
                Forms\Components\Select::make('type')->label(l('النوع', 'Type'))
                    ->options([
                        'discount' => l('خصم', 'Discount'),
                        'leave' => l('إجازة', 'Leave'),
                        'price_override' => l('تعديل السعر', 'Price Override'),
                        'other' => l('أخرى', 'Other'),
                    ])->required(),
                Forms\Components\TextInput::make('title')->label(l('العنوان', 'Title'))->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->label(l('الوصف', 'Description'))->required()->maxLength(2000),
                Forms\Components\Select::make('status')->label(l('الحالة', 'Status'))
                    ->options([
                        'new' => l('جديد', 'New'),
                        'approved' => l('تمت الموافقة', 'Approved'),
                        'rejected' => l('مرفوض', 'Rejected'),
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
                Tables\Columns\TextColumn::make('user.name')->label(l('المستخدم', 'User')),
                Tables\Columns\BadgeColumn::make('type')->label(l('النوع', 'Type'))
                    ->colors(['discount' => 'success', 'leave' => 'info', 'price_override' => 'warning', 'other' => 'gray']),
                Tables\Columns\TextColumn::make('title')->label(l('العنوان', 'Title'))->searchable(),
                Tables\Columns\BadgeColumn::make('status')->label(l('الحالة', 'Status'))
                    ->colors(['new' => 'info', 'approved' => 'warning', 'rejected' => 'danger', 'done' => 'success']),
                Tables\Columns\TextColumn::make('created_at')->label(l('التاريخ', 'Date'))->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')->label(l('المراجع', 'Reviewer'))->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(l('الحالة', 'Status'))
                    ->options([
                        'new' => l('جديد', 'New'),
                        'approved' => l('تمت الموافقة', 'Approved'),
                        'rejected' => l('مرفوض', 'Rejected'),
                        'done' => l('مكتمل', 'Done'),
                    ]),
                Tables\Filters\SelectFilter::make('type')->label(l('النوع', 'Type'))
                    ->options([
                        'discount' => l('خصم', 'Discount'),
                        'leave' => l('إجازة', 'Leave'),
                        'price_override' => l('تعديل السعر', 'Price Override'),
                        'other' => l('أخرى', 'Other'),
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'edit' => Pages\EditRequest::route('/{record}/edit'),
        ];
    }
}
