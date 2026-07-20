<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashReconciliationResource\Pages;
use App\Models\CashReconciliation;
use App\Services\CashReconciliationService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CashReconciliationResource extends Resource
{
    public static function getModel(): string
    {
        return CashReconciliation::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المالية' : 'Finance';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'تسوية نقدية' : 'Cash Reconciliation';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'التسويات النقدية' : 'Cash Reconciliations';
    }

    // Reconciliations are created by reps through the PWA, never in the panel.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $schema->schema([
            Forms\Components\Section::make($l('التفاصيل', 'Details'))->schema([
                Forms\Components\TextInput::make('expected_amount')->label($l('المتوقع', 'Expected'))->disabled(),
                Forms\Components\TextInput::make('counted_amount')->label($l('المعدود', 'Counted'))->disabled(),
                Forms\Components\TextInput::make('variance')->label($l('الفرق', 'Variance'))->disabled(),
                Forms\Components\Textarea::make('notes')->label($l('ملاحظات المندوب', 'Rep Notes'))->disabled(),
                Forms\Components\Textarea::make('review_notes')->label($l('ملاحظات المراجعة', 'Review Notes'))->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label($l('المندوب', 'Rep'))->searchable(),
                Tables\Columns\TextColumn::make('expected_amount')->label($l('المتوقع', 'Expected'))->numeric(2),
                Tables\Columns\TextColumn::make('counted_amount')->label($l('المعدود', 'Counted'))->numeric(2),
                Tables\Columns\TextColumn::make('variance')->label($l('الفرق', 'Variance'))->numeric(2)
                    ->color(fn (CashReconciliation $r) => (float) $r->variance === 0.0 ? 'success' : 'danger')
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('status')->label($l('الحالة', 'Status'))
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'flagged',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label($l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label($l('الحالة', 'Status'))
                    ->options([
                        'pending' => $l('معلق', 'Pending'),
                        'approved' => $l('معتمد', 'Approved'),
                        'flagged' => $l('مُعلّم', 'Flagged'),
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('approve')
                    ->label($l('اعتماد', 'Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CashReconciliation $r) => $r->status === 'pending' && auth()->user()->hasAnyRole(['admin', 'sales_manager', 'accounts']))
                    ->form([
                        Forms\Components\Textarea::make('review_notes')->label($l('ملاحظات (اختياري)', 'Notes (optional)'))->rows(2)->maxLength(500),
                    ])
                    ->requiresConfirmation()
                    ->action(function (CashReconciliation $r, array $data) use ($l) {
                        try {
                            app(CashReconciliationService::class)->approve($r, Auth::id(), $data['review_notes'] ?? '');
                            Notification::make()->success()->title($l('تم الاعتماد', 'Approved'))->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
                Action::make('flag')
                    ->label($l('تعليم', 'Flag'))
                    ->icon('heroicon-o-flag')
                    ->color('danger')
                    ->visible(fn (CashReconciliation $r) => $r->status === 'pending' && auth()->user()->hasAnyRole(['admin', 'sales_manager', 'accounts']))
                    ->form([
                        Forms\Components\Textarea::make('review_notes')->label($l('سبب التعليم', 'Reason'))->rows(2)->maxLength(500),
                    ])
                    ->requiresConfirmation()
                    ->action(function (CashReconciliation $r, array $data) use ($l) {
                        try {
                            app(CashReconciliationService::class)->flag($r, Auth::id(), $data['review_notes'] ?? '');
                            Notification::make()->success()->title($l('تم التعليم', 'Flagged'))->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashReconciliations::route('/'),
        ];
    }
}
