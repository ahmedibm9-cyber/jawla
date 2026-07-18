<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
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
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $schema->schema([
            Forms\Components\Section::make($l('بيانات الفاتورة', 'Invoice Info'))->schema([
                Forms\Components\Select::make('customer_id')->label($l('العميل', 'Customer'))
                    ->relationship('customer', 'name_ar')->required(),
                Forms\Components\Select::make('visit_id')->label($l('الزيارة', 'Visit'))
                    ->relationship('visit', 'id')->nullable(),
                Forms\Components\TextInput::make('invoice_number')->label($l('رقم الفاتورة', 'Number'))->disabled(),
                Forms\Components\Select::make('status')->label($l('الحالة', 'Status'))
                    ->options(['draft' => $l('مسودة', 'Draft'), 'submitted' => $l('مرسلة', 'Submitted'), 'partially_paid' => $l('مدفوعة جزئياً', 'Partially paid'), 'paid' => $l('مدفوعة', 'Paid'), 'cancelled' => $l('ملغاة', 'Cancelled')]),
                Forms\Components\TextInput::make('subtotal')->label($l('المجموع الفرعي', 'Subtotal'))->disabled(),
                Forms\Components\TextInput::make('vat_amount')->label($l('الضريبة', 'VAT'))->disabled(),
                Forms\Components\TextInput::make('total')->label($l('الإجمالي', 'Total'))->disabled(),
                Forms\Components\TextInput::make('paid_amount')->label($l('المدفوع', 'Paid'))->disabled(),
                Forms\Components\TextInput::make('remaining_amount')->label($l('المتبقي', 'Remaining'))->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label($l('رقم', 'Number'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name_ar')->label($l('العميل', 'Customer'))->searchable(),
                Tables\Columns\TextColumn::make('total')->label($l('الإجمالي', 'Total'))->money('egp'),
                Tables\Columns\TextColumn::make('remaining_amount')->label($l('المتبقي', 'Remaining')),
                Tables\Columns\BadgeColumn::make('status')->label($l('الحالة', 'Status'))
                    ->colors(['draft' => 'gray', 'submitted' => 'info', 'partially_paid' => 'warning', 'paid' => 'success', 'cancelled' => 'danger']),
                Tables\Columns\TextColumn::make('issued_at')->label($l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label($l('الحالة', 'Status'))
                    ->options(['draft' => 'Draft', 'submitted' => 'Submitted', 'partially_paid' => 'Partially Paid', 'paid' => 'Paid', 'cancelled' => 'Cancelled']),
            ])
            ->defaultSort('issued_at', 'desc')
            ->actions([
                Action::make('view_pdf')
                    ->label($l('عرض PDF', 'View PDF'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Invoice $r) => route('pdf.invoice', $r))
                    ->openUrlInNewTab(),
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-share')
                    ->url(fn (Invoice $r) => 'https://wa.me/?text='.urlencode(__('app.proforma_msg')." #{$r->invoice_number} - ".number_format((float) $r->total, 2).' EGP'))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
