<?php

namespace App\Filament\Resources;

use App\Enums\TaskStatus;
use App\Filament\Resources\TaskResource\Pages;
use App\Models\ApprovalRequest;
use App\Models\Task;
use App\Models\User;
use App\Services\OrganizationScopeService;
use App\Services\TaskService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaskResource extends Resource
{
    public static function getModel(): string
    {
        return Task::class;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any:task') ?? false;
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
                Forms\Components\Select::make('reviewer_id')
                    ->label(l('المراجع', 'Reviewer'))
                    ->relationship('reviewer', 'name')
                    ->searchable()
                    ->required(fn ($get): bool => (bool) $get('requires_approval')),
                Forms\Components\Select::make('final_approver_id')
                    ->label(l('المعتمد النهائي', 'Final Approver'))
                    ->relationship('finalApprover', 'name')
                    ->searchable()
                    ->different('reviewer_id')
                    ->nullable()
                    ->helperText(l('اختياري؛ يضيف مرحلة اعتماد ثانية', 'Optional; adds a second approval step')),
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
                Forms\Components\Select::make('priority')
                    ->label(l('الأولوية', 'Priority'))
                    ->options([
                        'low' => l('منخفضة', 'Low'),
                        'normal' => l('عادية', 'Normal'),
                        'high' => l('عالية', 'High'),
                        'urgent' => l('عاجلة', 'Urgent'),
                    ])
                    ->default('normal')
                    ->required(),
                Forms\Components\Toggle::make('requires_approval')
                    ->label(l('تتطلب اعتماداً', 'Requires approval'))
                    ->default(true)
                    ->live(),
                Forms\Components\Repeater::make('checklist')
                    ->label(l('قائمة التحقق', 'Checklist'))
                    ->schema([
                        Forms\Components\TextInput::make('label')
                            ->label(l('البند', 'Item'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('required')
                            ->label(l('إلزامي', 'Required'))
                            ->default(true),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->reorderable(),
                Forms\Components\Hidden::make('status')->default(TaskStatus::Assigned->value),
            ])->columns(2),
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
                    ->formatStateUsing(fn (TaskStatus|string $state): string => self::statusLabel($state))
                    ->color(fn (TaskStatus|string $state): string => self::statusColor($state)),
                TextColumn::make('priority')
                    ->label(l('الأولوية', 'Priority'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'urgent' => 'danger', 'high' => 'warning', 'low' => 'gray', default => 'info',
                    }),
                TextColumn::make('due_date')->label(l('تاريخ الاستحقاق', 'Due Date'))->date(),
                TextColumn::make('created_at')->label(l('تاريخ الإنشاء', 'Created'))->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(self::statusOptions()),
            ])
            ->actions([
                Action::make('approve')
                    ->label(l('اعتماد', 'Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Task $record): bool => self::isCurrentApprover($record, 'tasks.approve'))
                    ->requiresConfirmation()
                    ->modalDescription(l(
                        'سيتم تسجيل اعتماد هذه المهمة، وقد تنتقل إلى المعتمد التالي أو تُغلق نهائياً. الإجراء مُسجَّل.',
                        'This records your approval. The task will either advance to the next approver or close as approved. The action is logged.',
                    ))
                    ->action(function (Task $record): void {
                        app(TaskService::class)->approve($record->latestApproval, auth()->user());
                        Notification::make()->success()->title(l('تم الاعتماد', 'Task approved'))->send();
                    }),
                Action::make('request_changes')
                    ->label(l('طلب تعديلات', 'Request changes'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Task $record): bool => self::isCurrentApprover($record, 'tasks.reject'))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label(l('التعديلات المطلوبة', 'Required changes'))
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription(l(
                        'ستُعاد المهمة إلى المندوب مع السبب الموضح، ويمكنه تعديلها وإعادة إرسالها. الإجراء مُسجَّل.',
                        'The task will return to the rep with your reason and can be corrected and resubmitted. The action is logged.',
                    ))
                    ->action(function (Task $record, array $data): void {
                        app(TaskService::class)->requestChanges($record->latestApproval, auth()->user(), $data['reason']);
                        Notification::make()->success()->title(l('أُعيدت للمندوب', 'Returned to rep'))->send();
                    }),
                Action::make('reject')
                    ->label(l('رفض', 'Reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Task $record): bool => self::isCurrentApprover($record, 'tasks.reject'))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label(l('سبب الرفض', 'Rejection reason'))
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription(l(
                        'سيُسجل رفض المهمة ويُخطر المندوب بالسبب. يستطيع المندوب استئنافها وإعادة إرسالها. الإجراء مُسجَّل.',
                        'This rejects the task and notifies the rep with the reason. The rep may resume and resubmit it. The action is logged.',
                    ))
                    ->action(function (Task $record, array $data): void {
                        app(TaskService::class)->reject($record->latestApproval, auth()->user(), $data['reason']);
                        Notification::make()->success()->title(l('تم الرفض', 'Task rejected'))->send();
                    }),
                Action::make('reopen')
                    ->label(l('إعادة فتح', 'Reopen'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Task $record): bool => $record->status === TaskStatus::Approved && (auth()->user()?->can('tasks.reopen') ?? false))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label(l('سبب إعادة الفتح', 'Reopen reason'))
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription(l(
                        'ستُعاد المهمة المعتمدة إلى المندوب للتنفيذ مجدداً، مع الاحتفاظ بسجل الاعتماد السابق. الإجراء مُسجَّل.',
                        'The approved task will return to the rep for more work while preserving its prior approval history. The action is logged.',
                    ))
                    ->action(function (Task $record, array $data): void {
                        app(TaskService::class)->reopen($record, auth()->user(), $data['reason']);
                        Notification::make()->success()->title(l('أُعيد فتح المهمة', 'Task reopened'))->send();
                    }),
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['latestApproval.steps']);
        $actor = auth()->user();

        if ($actor === null) {
            return $query->whereRaw('1 = 0');
        }

        $visibleUsers = app(OrganizationScopeService::class)
            ->scopeUsers(User::query()->select('users.id'), $actor);

        return $query->whereIn('assigned_to', $visibleUsers);
    }

    /** @return array<string, string> */
    private static function statusOptions(): array
    {
        return collect(TaskStatus::cases())->mapWithKeys(
            fn (TaskStatus $status): array => [$status->value => self::statusLabel($status)],
        )->all();
    }

    private static function statusLabel(TaskStatus|string $status): string
    {
        $value = $status instanceof TaskStatus ? $status->value : $status;

        return match ($value) {
            'draft' => l('مسودة', 'Draft'),
            'assigned' => l('مسندة', 'Assigned'),
            'accepted' => l('مقبولة', 'Accepted'),
            'in_progress' => l('قيد التنفيذ', 'In progress'),
            'submitted' => l('بانتظار الاعتماد', 'Awaiting approval'),
            'changes_requested' => l('تحتاج تعديلات', 'Changes requested'),
            'rejected' => l('مرفوضة', 'Rejected'),
            'approved' => l('معتمدة', 'Approved'),
            'reopened' => l('أُعيد فتحها', 'Reopened'),
            'cancelled' => l('ملغاة', 'Cancelled'),
            default => $value,
        };
    }

    private static function statusColor(TaskStatus|string $status): string
    {
        $value = $status instanceof TaskStatus ? $status->value : $status;

        return match ($value) {
            'approved' => 'success',
            'rejected', 'cancelled' => 'danger',
            'changes_requested', 'reopened' => 'warning',
            'submitted' => 'info',
            default => 'gray',
        };
    }

    private static function isCurrentApprover(Task $task, string $permission): bool
    {
        if ($task->status !== TaskStatus::Submitted || ! (auth()->user()?->can($permission) ?? false)) {
            return false;
        }

        $request = $task->latestApproval;
        if (! $request instanceof ApprovalRequest) {
            return false;
        }

        $currentStep = $request->steps->firstWhere('sequence', $request->current_sequence);

        return (int) $currentStep?->approver_id === (int) auth()->id();
    }
}
