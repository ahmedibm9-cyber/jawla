<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectionSubmissionResource\Pages;
use App\Models\CollectionSubmission;
use App\Services\CollectionSubmissionService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CollectionSubmissionResource extends Resource
{
    protected static ?string $model = CollectionSubmission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationGroup(): ?string
    {
        return l('المالية', 'Finance');
    }

    public static function getLabel(): string
    {
        return l('تحصيل للمراجعة', 'Collection submission');
    }

    public static function getPluralLabel(): string
    {
        return l('مراجعة التحصيلات', 'Collection review');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('customer.name_ar')->label(l('العميل', 'Customer'))->searchable(),
            Tables\Columns\TextColumn::make('user.name')->label(l('المندوب', 'Rep')),
            Tables\Columns\TextColumn::make('amount')->label(l('المبلغ', 'Amount'))->money('egp'),
            Tables\Columns\TextColumn::make('method')->label(l('الطريقة', 'Method'))->badge(),
            Tables\Columns\TextColumn::make('reference_number')->label(l('المرجع', 'Reference')),
            Tables\Columns\TextColumn::make('photos_count')->counts('photos')->label(l('المرفقات', 'Evidence')),
            Tables\Columns\TextColumn::make('status')->label(l('الحالة', 'Status'))->badge(),
        ])->actions([
            Action::make('approve')->label(l('اعتماد وترحيل', 'Approve & post'))->color('success')
                ->visible(fn (CollectionSubmission $record): bool => $record->status === 'pending_review' && (auth()->user()?->can('collections.review') ?? false))
                ->requiresConfirmation()->modalDescription(l('سيُنشأ سند قبض وتُرحّل الحركة إلى رصيد العميل والصندوق. هذا إجراء مالي مُسجَّل.', 'A payment receipt will be created and posted to the customer and cash-box balances. This is a logged financial action.'))
                ->action(function (CollectionSubmission $record): void {
                    app(CollectionSubmissionService::class)->approve($record->latestApproval, auth()->user());
                    Notification::make()->success()->title(l('تم الترحيل', 'Collection posted'))->send();
                }),
            Action::make('reject')->label(l('رفض', 'Reject'))->color('danger')
                ->visible(fn (CollectionSubmission $record): bool => $record->status === 'pending_review' && (auth()->user()?->can('collections.review') ?? false))
                ->form([Forms\Components\Textarea::make('reason')->label(l('السبب', 'Reason'))->required()->maxLength(1000)])
                ->requiresConfirmation()->modalDescription(l('لن تُرحّل الحركة المالية وسيُخطر المندوب بسبب الرفض. الإجراء مُسجَّل.', 'No financial movement will be posted and the rep will see the rejection reason. The action is logged.'))
                ->action(fn (CollectionSubmission $record, array $data) => app(CollectionSubmissionService::class)->reject($record->latestApproval, auth()->user(), $data['reason'])),
        ])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCollectionSubmissions::route('/')];
    }
}
