<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesTargetResource\Pages;
use App\Models\SalesTarget;
use App\Models\User;
use App\Services\AttainmentService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SalesTargetResource extends Resource
{
    public static function getModel(): string
    {
        return SalesTarget::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-flag';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'هدف مبيعات' : 'Sales Target';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'أهداف المبيعات' : 'Sales Targets';
    }

    public static function form(Schema $schema): Schema
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;
        $companyId = Auth::user()?->company_id;

        return $schema->schema([
            Section::make($l('الهدف', 'Target'))->schema([
                Forms\Components\Select::make('user_id')->label($l('المندوب', 'Rep'))
                    ->options(fn () => User::where('company_id', $companyId)
                        ->whereHas('roles', fn ($q) => $q->where('name', 'rep'))
                        ->pluck('name', 'id'))
                    ->searchable()->required(),
                Forms\Components\DatePicker::make('period_start')->label($l('بداية الفترة', 'Period Start'))
                    ->required()->default(now()->startOfMonth()),
                Forms\Components\DatePicker::make('period_end')->label($l('نهاية الفترة', 'Period End'))
                    ->required()->default(now()->endOfMonth())->afterOrEqual('period_start'),
                Forms\Components\TextInput::make('target_amount')->label($l('قيمة الهدف', 'Target Amount'))
                    ->numeric()->required()->minValue(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label($l('المندوب', 'Rep'))->searchable(),
                Tables\Columns\TextColumn::make('period_start')->label($l('من', 'From'))->date(),
                Tables\Columns\TextColumn::make('period_end')->label($l('إلى', 'To'))->date(),
                Tables\Columns\TextColumn::make('target_amount')->label($l('الهدف', 'Target'))->numeric(2),
                Tables\Columns\TextColumn::make('actual')->label($l('المحقق', 'Actual'))
                    ->state(fn (SalesTarget $r) => number_format(app(AttainmentService::class)->attainment($r)['actual'], 2)),
                Tables\Columns\TextColumn::make('percent')->label($l('نسبة التحقيق', 'Attainment'))
                    ->state(fn (SalesTarget $r) => app(AttainmentService::class)->attainment($r)['percent'].'%')
                    ->badge()
                    ->color(function (SalesTarget $r) {
                        $p = app(AttainmentService::class)->attainment($r)['percent'];

                        return $p >= 100 ? 'success' : ($p >= 60 ? 'warning' : 'danger');
                    }),
            ])
            ->defaultSort('period_start', 'desc')
            ->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesTargets::route('/'),
            'create' => Pages\CreateSalesTarget::route('/create'),
            'edit' => Pages\EditSalesTarget::route('/{record}/edit'),
        ];
    }
}
