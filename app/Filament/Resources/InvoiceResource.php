<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Services\Eta\EtaService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    public static function getModel(): string
    {
        return Invoice::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الفواتير' : 'Invoices';
    }

    public static function form(Schema $schema): Schema
    {

        return $schema->schema([
            Section::make(l('بيانات الفاتورة', 'Invoice Info'))->schema([
                Forms\Components\Select::make('customer_id')->label(l('العميل', 'Customer'))
                    ->relationship('customer', 'name_ar')->required(),
                Forms\Components\Select::make('visit_id')->label(l('الزيارة', 'Visit'))
                    ->relationship('visit', 'id')->nullable(),
                Forms\Components\TextInput::make('invoice_number')->label(l('رقم الفاتورة', 'Number'))->disabled(),
                Forms\Components\TextInput::make('status')->label(l('الحالة', 'Status'))->disabled(),
                Forms\Components\TextInput::make('subtotal')->label(l('المجموع الفرعي', 'Subtotal'))->disabled(),
                Forms\Components\TextInput::make('vat_amount')->label(l('الضريبة', 'VAT'))->disabled(),
                Forms\Components\TextInput::make('total')->label(l('الإجمالي', 'Total'))->disabled(),
                Forms\Components\TextInput::make('paid_amount')->label(l('المدفوع', 'Paid'))->disabled(),
                Forms\Components\TextInput::make('remaining_amount')->label(l('المتبقي', 'Remaining'))->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label(l('رقم', 'Number'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name_ar')->label(l('العميل', 'Customer'))->searchable(),
                Tables\Columns\TextColumn::make('total')->label(l('الإجمالي', 'Total'))->money('egp'),
                Tables\Columns\TextColumn::make('remaining_amount')->label(l('المتبقي', 'Remaining')),
                Tables\Columns\BadgeColumn::make('status')->label(l('الحالة', 'Status'))
                    ->colors(['draft' => 'gray', 'submitted' => 'info', 'partially_paid' => 'warning', 'paid' => 'success', 'cancelled' => 'danger']),
                Tables\Columns\BadgeColumn::make('eta_status')->label(l('حالة الضريبة', 'ETA Status'))
                    ->colors(['pending' => 'gray', 'submitted' => 'info', 'rejected' => 'danger', 'accepted' => 'success'])
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('issued_at')->label(l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(l('الحالة', 'Status'))
                    ->options(['draft' => 'Draft', 'submitted' => 'Submitted', 'partially_paid' => 'Partially Paid', 'paid' => 'Paid', 'cancelled' => 'Cancelled']),
                Tables\Filters\SelectFilter::make('eta_status')->label(l('حالة الضريبة', 'ETA Status'))
                    ->options(['pending' => 'Pending', 'submitted' => 'Submitted', 'rejected' => 'Rejected', 'accepted' => 'Accepted']),
            ])
            ->defaultSort('issued_at', 'desc')
            ->actions([
                Action::make('view_pdf')
                    ->label(l('عرض PDF', 'View PDF'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Invoice $r) => url("/app/pdf/invoice/{$r->id}"))
                    ->openUrlInNewTab(),
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-share')
                    ->url(fn (Invoice $r) => 'https://wa.me/?text='.urlencode(__('app.proforma_msg')." #{$r->invoice_number} - ".number_format((float) $r->total, 2).' EGP'))
                    ->openUrlInNewTab(),
                Action::make('resubmit_eta')
                    ->label(l('إعادة إرسال للضريبة', 'Resubmit to ETA'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Invoice $r) => in_array($r->eta_status, ['rejected', null]) && $r->company?->eta_enabled)
                    ->requiresConfirmation()
                    ->modalHeading(l('إعادة إرسال الفاتورة', 'Resubmit Invoice'))
                    ->modalDescription(l('هل أنت متأكد من إعادة إرسال هذه الفاتورة لهيئة الضرائب؟', 'Are you sure you want to resubmit this invoice to the tax authority?'))
                    ->modalSubmitActionLabel(l('إعادة إرسال', 'Resubmit'))
                    ->action(function (Invoice $r) {
                        $result = app(EtaService::class)->resubmit($r);

                        Notification::make()
                            ->title($result->eta_status === 'submitted' ? l('تم الإرسال بنجاح', 'Submitted successfully') : l('فشل الإرسال', 'Submission failed'))
                            ->status($result->eta_status === 'submitted' ? 'success' : 'danger')
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
        ];
    }
}
