<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProformaInvoiceResource\Pages;
use App\Models\ProformaInvoice;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;

class ProformaInvoiceResource extends Resource
{
    public static function getModel(): string
    {
        return ProformaInvoice::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-duplicate';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'الفواتير المبدئية' : 'Proforma Invoices';
    }

    public static function form(Schema $schema): Schema
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $schema->schema([
            Forms\Components\Section::make($l('بيانات', 'Info'))->schema([
                Forms\Components\TextInput::make('proforma_number')->label($l('رقم', 'Number'))->disabled(),
                Forms\Components\Select::make('customer_id')->label($l('العميل', 'Customer'))
                    ->relationship('customer', 'name_ar'),
                Forms\Components\TextInput::make('total')->label($l('الإجمالي', 'Total'))->disabled(),
                Forms\Components\Select::make('status')->label($l('الحالة', 'Status'))
                    ->options(['sent' => $l('مرسلة', 'Sent'), 'converted_to_invoice' => $l('محول لفاتورة', 'Converted'), 'expired' => $l('منتهي', 'Expired'), 'cancelled' => $l('ملغي', 'Cancelled')]),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('proforma_number')->label($l('رقم', 'Number'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name_ar')->label($l('العميل', 'Customer'))->searchable(),
                Tables\Columns\TextColumn::make('total')->label($l('الإجمالي', 'Total')),
                Tables\Columns\BadgeColumn::make('status')->label($l('الحالة', 'Status'))
                    ->colors(['sent' => 'info', 'converted_to_invoice' => 'success', 'expired' => 'warning', 'cancelled' => 'danger']),
                Tables\Columns\TextColumn::make('posting_date')->label($l('التاريخ', 'Date'))->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label($l('الحالة', 'Status'))
                    ->options(['sent' => 'Sent', 'converted_to_invoice' => 'Converted', 'expired' => 'Expired', 'cancelled' => 'Cancelled']),
            ])
            ->defaultSort('posting_date', 'desc')
            ->actions([
                Action::make('view_pdf')
                    ->label($l('عرض PDF', 'View PDF'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (ProformaInvoice $r) => route('pdf.proforma', $r))
                    ->openUrlInNewTab(),
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-share')
                    ->url(fn (ProformaInvoice $r) => 'https://wa.me/?text='.urlencode(__('app.proforma_msg')." #{$r->proforma_number} - ".number_format((float) $r->total, 2).' EGP'))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProformaInvoices::route('/'),
            'edit' => Pages\EditProformaInvoice::route('/{record}/edit'),
        ];
    }
}
