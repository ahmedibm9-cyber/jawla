<?php

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ReversalService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithPagination;

class ActivityLog extends Page
{
    use WithPagination;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clock';

    protected string $view = 'filament.pages.activity-log';

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'إدارة' : 'Management';
    }

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'سجل الأنشطة' : 'Activity Log';
    }

    public function getTitle(): string
    {
        return app()->getLocale() === 'ar' ? 'سجل الأنشطة' : 'Activity Log';
    }

    public ?string $typeFilter = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasAnyRole(['admin', 'sales_manager']) ?? false;
    }

    public function reverse(int $activityId): void
    {
        $activity = Activity::findOrFail($activityId);
        $user = auth()->user();

        if (! $user || ! $user->hasAnyRole(['admin', 'sales_manager'])) {
            Notification::make()->danger()->title(__('Unauthorized'))->send();

            return;
        }

        try {
            if (in_array($activity->type, ['invoice_created', 'invoice_submitted'])) {
                $invoice = Invoice::find($activity->subject_id);
                if ($invoice && ! in_array($invoice->status->value, ['cancelled', 'amended'])) {
                    app(ReversalService::class)->reverseInvoice($invoice, 'Reversed from activity log by '.$user->name);
                    Notification::make()->success()->title(app()->getLocale() === 'ar' ? 'تم إلغاء الفاتورة' : 'Invoice reversed')->send();
                }
            } elseif ($activity->type === 'payment_collected') {
                $payment = Payment::find($activity->subject_id);
                if ($payment && ! $payment->cancelled_at) {
                    app(ReversalService::class)->reversePayment($payment, 'Reversed from activity log by '.$user->name);
                    Notification::make()->success()->title(app()->getLocale() === 'ar' ? 'تم إلغاء الدفعة' : 'Payment reversed')->send();
                }
            } else {
                Notification::make()->warning()->title(app()->getLocale() === 'ar' ? 'لا يمكن إلغاء هذا النوع' : 'Cannot reverse this type')->send();
            }
        } catch (\Throwable $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }

    public function getActivitiesProperty()
    {
        $q = Activity::with('user')->latest();

        if ($this->typeFilter) {
            $q->where('type', $this->typeFilter);
        }

        return $q->paginate(50);
    }
}
