<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    public static function getModel(): string
    {
        return User::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'الشركة' : 'Company';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'مستخدم' : 'User';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'المستخدمين' : 'Users';
    }

    public static function form(Schema $schema): Schema
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $schema->schema([
            Forms\Components\Section::make($l('بيانات المستخدم', 'User Info'))->schema([
                Forms\Components\TextInput::make('name')->label($l('الاسم', 'Name'))->required(),
                Forms\Components\TextInput::make('email')->label($l('البريد الإلكتروني', 'Email'))->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')->label($l('الهاتف', 'Phone'))->tel(),
                Forms\Components\TextInput::make('employee_code')->label($l('كود الموظف', 'Employee Code'))->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')->label($l('كلمة المرور', 'Password'))->password()
                    ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)),
                Forms\Components\Select::make('company_id')->label($l('الشركة', 'Company'))->relationship('company', 'name_ar')->preload()->required(),
                Forms\Components\Select::make('roles')->label($l('الصلاحية', 'Role'))
                    ->relationship('roles', 'name')->preload()
                    ->required(),
                Forms\Components\Toggle::make('is_active')->label($l('نشط', 'Active'))->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label($l('الاسم', 'Name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label($l('البريد', 'Email'))->searchable(),
                Tables\Columns\TextColumn::make('employee_code')->label($l('الكود', 'Code')),
                Tables\Columns\TextColumn::make('roles.name')->label($l('الصلاحية', 'Roles'))->formatStateUsing(fn ($state) => collect($state)->join(', ')),
                Tables\Columns\TextColumn::make('company.name_ar')->label($l('الشركة', 'Company')),
                Tables\Columns\IconColumn::make('is_active')->label($l('نشط', 'Active'))->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')->label($l('الشركة', 'Company'))->relationship('company', 'name_ar'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
