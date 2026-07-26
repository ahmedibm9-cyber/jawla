<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CollectPayment extends Page
{
    protected string $view = 'filament.pages.collect-payment';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->can('payments.collect') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'تحصيل دفعة' : 'Collect Payment';
    }

    public ?int $customer_id = null;

    public ?int $invoice_id = null;

    public ?float $amount = null;

    public string $method = 'cash';

    public ?string $notes = null;

    public static function form(Schema $schema): Schema
    {

        return $schema->schema([
            Forms\Components\Select::make('customer_id')
                ->label(l('العميل', 'Customer'))
                ->options(fn () => Customer::where('company_id', Auth::user()->activeCompanyId())
                    ->where('is_active', true)
                    ->orderBy('name_ar')
                    ->pluck('name_ar', 'id'))
                ->reactive()
                ->required(),
            Forms\Components\Select::make('invoice_id')
                ->label(l('فاتورة', 'Invoice'))
                ->options(fn (callable $get) => Invoice::where('customer_id', $get('customer_id'))
                    ->whereIn('status', ['submitted', 'partially_paid'])
                    ->whereRaw('remaining_amount > 0')
                    ->pluck('invoice_number', 'id'))
                ->helperText(l('اختياري', 'Optional'))
                ->reactive()
                ->afterStateUpdated(function (callable $set, $state) {
                    $inv = Invoice::find($state);
                    if ($inv) {
                        $set('amount', (float) $inv->remaining_amount);
                    }
                }),
            Forms\Components\TextInput::make('amount')
                ->label(l('المبلغ', 'Amount'))
                ->numeric()
                ->required(),
            Forms\Components\Select::make('method')
                ->label(l('طريقة الدفع', 'Method'))
                ->options(['cash' => l('نقدي', 'Cash'), 'cheque' => l('شيك', 'Cheque'), 'transfer' => l('تحويل', 'Transfer'), 'other' => l('أخرى', 'Other')])
                ->default('cash')
                ->required(),
            Forms\Components\Textarea::make('notes')
                ->label(l('ملاحظات', 'Notes')),
        ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $payment = app(PaymentService::class)->collect(
            companyId: Auth::user()->activeCompanyId(),
            userId: Auth::id(),
            customerId: $data['customer_id'],
            amount: (float) $data['amount'],
            method: $data['method'],
            invoiceId: $data['invoice_id'] ?? null,
            notes: $data['notes'] ?? null,
        );

        Notification::make()
            ->title(app()->getLocale() === 'ar' ? 'تم تحصيل الدفعة' : 'Payment collected')
            ->success()
            ->send();

        $this->reset([
            'customer_id', 'invoice_id', 'amount', 'method', 'notes',
        ]);
        $this->method = 'cash';
    }
}
