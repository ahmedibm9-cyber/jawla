<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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

        return $schema->schema([
            Section::make(l('بيانات المستخدم', 'User Info'))->schema([
                Forms\Components\TextInput::make('name')->label(l('الاسم', 'Name'))->required(),
                Forms\Components\TextInput::make('email')->label(l('البريد الإلكتروني', 'Email'))->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')->label(l('الهاتف', 'Phone'))->tel(),
                Forms\Components\TextInput::make('employee_code')->label(l('كود الموظف', 'Employee Code'))->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')->label(l('كلمة المرور', 'Password'))->password()
                    ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)),
                Forms\Components\Select::make('company_id')->label(l('الشركة', 'Company'))->relationship('company', 'name_ar')->preload()->required(),
                Forms\Components\Select::make('primary_organization_unit_id')->label(l('الوحدة التنظيمية الأساسية', 'Primary organization unit'))
                    ->relationship('primaryOrganizationUnit', 'name_ar')->searchable()->preload(),
                Forms\Components\Select::make('organizationUnits')->label(l('نطاق الوحدات', 'Organization scope'))
                    ->relationship('organizationUnits', 'name_ar')->multiple()->searchable()->preload()
                    ->helperText(l('يحدد الفروع والمناطق والفرق التي يستطيع المدير الإشراف عليها', 'Defines the branches, areas, and teams this manager can supervise')),
                Forms\Components\Select::make('roles')->label(l('الصلاحية', 'Role'))
                    ->relationship('roles', 'name')->preload()
                    ->required(),
                Forms\Components\Toggle::make('is_active')->label(l('نشط', 'Active'))->default(true),
            ])->columns(2),
            Section::make(l('ملف المندوب', 'Representative profile'))
                ->relationship('representativeProfile')
                ->schema([
                    Forms\Components\Select::make('supervisor_id')->label(l('المشرف', 'Supervisor'))
                        ->relationship('supervisor', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('job_title')->label(l('المسمى الوظيفي', 'Job title'))->maxLength(255),
                    Forms\Components\DatePicker::make('hire_date')->label(l('تاريخ التعيين', 'Hire date')),
                    Forms\Components\TextInput::make('vehicle_code')->label(l('كود المركبة', 'Vehicle code'))->maxLength(255),
                    Forms\Components\TextInput::make('national_id')->label(l('الرقم القومي', 'National ID'))->maxLength(255),
                    Forms\Components\TextInput::make('emergency_contact')->label(l('اتصال الطوارئ', 'Emergency contact'))->maxLength(255),
                    Forms\Components\TagsInput::make('skills')->label(l('المهارات', 'Skills')),
                ])->columns(2)->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->modifyQueryUsing(function ($query) {
                $user = auth()->user();
                if ($user && ! $user->isSuperAdmin()) {
                    $query->whereDoesntHave('roles', function ($q) {
                        $q->where('name', 'super_admin');
                    });
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(l('الاسم', 'Name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label(l('البريد', 'Email'))->searchable(),
                Tables\Columns\TextColumn::make('employee_code')->label(l('الكود', 'Code')),
                Tables\Columns\TextColumn::make('roles.name')->label(l('الصلاحية', 'Roles'))->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state),
                Tables\Columns\TextColumn::make('company.name_ar')->label(l('الشركة', 'Company')),
                Tables\Columns\TextColumn::make('primaryOrganizationUnit.name_ar')->label(l('الوحدة', 'Unit')),
                Tables\Columns\IconColumn::make('is_active')->label(l('نشط', 'Active'))->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')->label(l('الشركة', 'Company'))->relationship('company', 'name_ar'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
