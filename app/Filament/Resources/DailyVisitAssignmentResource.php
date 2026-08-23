<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyVisitAssignmentResource\Pages;
use App\Models\DailyVisitAssignment;
use App\Models\Route;
use App\Services\DailyVisitAssignmentService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DailyVisitAssignmentResource extends Resource
{
    public static function getModel(): string
    {
        return DailyVisitAssignment::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'تعيين زيارة' : 'Visit Assignment';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'تعيينات الزيارات' : 'Visit Assignments';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(l('بيانات', 'Info'))->schema([
                Forms\Components\Select::make('user_id')
                    ->label(l('المندوب', 'Rep'))
                    ->relationship('user', 'name')
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->label(l('العميل', 'Customer'))
                    ->relationship('customer', 'name_ar')
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('visit_date')
                    ->label(l('تاريخ الزيارة', 'Visit Date'))
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->label(l('الترتيب', 'Sort Order'))
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('status')
                    ->label(l('الحالة', 'Status'))
                    ->options(fn () => auth()->user()?->can('visit_plans.approve') ? [
                        'draft' => l('مسودة', 'Draft'),
                        'pending_approval' => l('بانتظار الاعتماد', 'Pending Approval'),
                        'approved' => l('معتمد', 'Approved'),
                        'rejected' => l('مرفوض', 'Rejected'),
                        'completed' => l('مكتمل', 'Completed'),
                    ] : [
                        'draft' => l('مسودة', 'Draft'),
                        'pending_approval' => l('بانتظار الاعتماد', 'Pending Approval'),
                    ])
                    ->default('draft'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label(l('المندوب', 'Rep'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name_ar')->label(l('العميل', 'Customer'))->searchable(),
                Tables\Columns\TextColumn::make('visit_date')->label(l('التاريخ', 'Date'))->date()->sortable(),
                Tables\Columns\BadgeColumn::make('status')->label(l('الحالة', 'Status'))
                    ->colors([
                        'draft' => 'gray',
                        'pending_approval' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'info',
                    ]),
                Tables\Columns\TextColumn::make('sort_order')->label(l('الترتيب', 'Order'))->sortable(),
                Tables\Columns\TextColumn::make('approvedByUser.name')->label(l('اعتمد بواسطة', 'Approved By'))->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')->label(l('المندوب', 'Rep'))->relationship('user', 'name'),
                Tables\Filters\SelectFilter::make('status')->label(l('الحالة', 'Status'))
                    ->options([
                        'draft' => l('مسودة', 'Draft'),
                        'pending_approval' => l('بانتظار الاعتماد', 'Pending Approval'),
                        'approved' => l('معتمد', 'Approved'),
                        'rejected' => l('مرفوض', 'Rejected'),
                        'completed' => l('مكتمل', 'Completed'),
                    ]),
            ])
            ->defaultSort('visit_date', 'desc')
            ->actions([
                EditAction::make(),
                Action::make('submit')
                    ->label(l('إرسال للاعتماد', 'Submit'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn (DailyVisitAssignment $record): bool => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalDescription(l(' سيتم إرسال تعيين الزيارة للاعتماد.', 'This visit plan will be submitted for manager approval.'))
                    ->action(function (DailyVisitAssignment $record): void {
                        app(DailyVisitAssignmentService::class)->submit($record, auth()->user());
                        Notification::make()->success()->title(l('تم الإرسال', 'Submitted'))->send();
                    }),
                Action::make('approve')
                    ->label(l('اعتماد', 'Approve'))
                    ->color('success')
                    ->visible(fn (DailyVisitAssignment $record): bool => $record->status === 'pending_approval' && $record->latestApproval !== null && (auth()->user()?->can('visit_plans.approve') ?? false)
                    )
                    ->requiresConfirmation()
                    ->modalDescription(l('سيصبح تعيين الزيارة معتمداً ويظهر للمندوب.', 'The visit plan will be approved and visible to the rep.'))
                    ->action(function (DailyVisitAssignment $record): void {
                        app(DailyVisitAssignmentService::class)->approve($record->latestApproval, auth()->user());
                        Notification::make()->success()->title(l('تم الاعتماد', 'Approved'))->send();
                    }),
                Action::make('reject')
                    ->label(l('رفض', 'Reject'))
                    ->color('danger')
                    ->visible(fn (DailyVisitAssignment $record): bool => $record->status === 'pending_approval' && $record->latestApproval !== null && (auth()->user()?->can('visit_plans.approve') ?? false)
                    )
                    ->form([Forms\Components\Textarea::make('reason')->label(l('السبب', 'Reason'))->required()->maxLength(1000)])
                    ->requiresConfirmation()
                    ->modalDescription(l('سيتم رفض تعيين الزيارة وإظهار السبب. الإجراء مُسجَّل.', 'The visit plan will be rejected and the reason logged.'))
                    ->action(fn (DailyVisitAssignment $record, array $data) => app(DailyVisitAssignmentService::class)->reject($record->latestApproval, auth()->user(), $data['reason'])
                    ),
            ])
            ->bulkActions([
                BulkAction::make('bulkAssign')
                    ->label(l('تعيين جماعي', 'Bulk Assign'))
                    ->icon('heroicon-o-user-group')
                    ->requiresConfirmation()
                    ->modalDescription(l('سيتم إنشاء تعيينات زيارة للعملاء المحددين.', 'Visit assignments will be created for the selected customers.'))
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label(l('المندوب', 'Rep'))
                            ->relationship('user', 'name')
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('visit_date')
                            ->label(l('تاريخ الزيارة', 'Visit Date'))
                            ->required(),
                        Forms\Components\Select::make('customer_ids')
                            ->label(l('العملاء', 'Customers'))
                            ->relationship('customer', 'name_ar')
                            ->preload()
                            ->multiple()
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $result = app(DailyVisitAssignmentService::class)->bulkAssign(
                            auth()->user(),
                            (int) $data['user_id'],
                            $data['visit_date'],
                            $data['customer_ids'],
                        );
                        Notification::make()->success()
                            ->title(l('تم التعيين', 'Assigned'))
                            ->body("{$result['created']} ".l('جديد', 'created').", {$result['skipped']} ".l('مكرر', 'skipped'))
                            ->send();
                    }),
                BulkAction::make('assignFromRoute')
                    ->label(l('تعيين من خط سير', 'Assign from Route'))
                    ->icon('heroicon-o-map')
                    ->requiresConfirmation()
                    ->modalDescription(l('سيتم إنشاء تعيينات زيارة لجميع عملاء خط السير.', 'Visit assignments will be created for all customers on the route.'))
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label(l('المندوب', 'Rep'))
                            ->relationship('user', 'name')
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('visit_date')
                            ->label(l('تاريخ الزيارة', 'Visit Date'))
                            ->required(),
                        Forms\Components\Select::make('route_id')
                            ->label(l('خط السير', 'Route'))
                            ->options(fn () => Route::where('company_id', auth()->user()->activeCompanyId())->pluck('name_en', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $route = Route::where('company_id', auth()->user()->activeCompanyId())->findOrFail((int) $data['route_id']);
                        $customerIds = $route->customers()->pluck('id')->toArray();
                        $result = app(DailyVisitAssignmentService::class)->bulkAssign(
                            auth()->user(),
                            (int) $data['user_id'],
                            $data['visit_date'],
                            $customerIds,
                        );
                        Notification::make()->success()
                            ->title(l('تم التعيين', 'Assigned'))
                            ->body("{$result['created']} ".l('جديد', 'created').", {$result['skipped']} ".l('مكرر', 'skipped'))
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyVisitAssignments::route('/'),
            'create' => Pages\CreateDailyVisitAssignment::route('/create'),
            'edit' => Pages\EditDailyVisitAssignment::route('/{record}/edit'),
        ];
    }
}
