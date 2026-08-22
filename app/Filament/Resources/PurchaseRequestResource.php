<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseRequestResource\Pages;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestResource extends Resource
{
    public static function getModel(): string
    {
        return PurchaseRequest::class;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any:purchase_request') ?? false;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-shopping-cart';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'عرض شراء' : 'Purchase Offer';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'عروض الشراء' : 'Purchase Offers';
    }

    public static function form(Schema $schema): Schema
    {

        return $schema->schema([
            Section::make(l('بيانات', 'Info'))->schema([
                Forms\Components\Select::make('user_id')->label(l('المندوب', 'Rep'))
                    ->relationship('user', 'name')->preload(),
                Forms\Components\Select::make('supplier_id')->label(l('المورد', 'Supplier'))
                    ->relationship('supplier', 'name_ar')->preload(),
                Forms\Components\Select::make('product_id')->label(l('المنتج', 'Product'))
                    ->relationship('product', 'name_ar')->preload(),
                Forms\Components\TextInput::make('quantity')->label(l('الكمية', 'Quantity'))->numeric(),
                Forms\Components\TextInput::make('offered_price')->label(l('السعر المعروض', 'Offered Price'))->numeric(),
                Forms\Components\TextInput::make('currency')->label(l('العملة', 'Currency'))->default('EGP'),
                Forms\Components\DatePicker::make('expires_at')->label(l('تاريخ انتهاء العرض', 'Offer Expires At')),
                Forms\Components\Textarea::make('payment_terms')->label(l('شروط الدفع', 'Payment Terms')),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label(l('المندوب', 'Rep')),
                Tables\Columns\TextColumn::make('supplier.name_ar')->label(l('المورد', 'Supplier'))
                    ->formatStateUsing(fn ($s, $r) => $r->supplier?->name_ar ?? $r->supplier?->name_en ?? l('غير محدد', 'N/A')),
                Tables\Columns\TextColumn::make('product.name_ar')->label(l('المنتج', 'Product'))->searchable(),
                Tables\Columns\TextColumn::make('quantity')->label(l('الكمية', 'Qty')),
                Tables\Columns\TextColumn::make('offered_price')->label(l('السعر', 'Price')),
                Tables\Columns\BadgeColumn::make('status')->label(l('الحالة', 'Status'))
                    ->colors([
                        'pending' => 'warning',
                        'sales_approved' => 'info',
                        'purchasing_approved' => 'success',
                        'rejected_by_sales' => 'danger',
                        'rejected_by_purchasing' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('expires_at')->label(l('ينتهي في', 'Expires'))
                    ->date()
                    ->placeholder('—')
                    ->color(fn (PurchaseRequest $r) => $r->isExpired() ? 'danger' : null),
                Tables\Columns\TextColumn::make('purchaseOrder.order_number')->label(l('أمر الشراء', 'PO #'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label(l('التاريخ', 'Date'))->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(l('الحالة', 'Status'))
                    ->options([
                        'pending' => l('معلق', 'Pending'),
                        'sales_approved' => l('Sales وافق', 'Sales approved'),
                        'purchasing_approved' => l('Purchasing وافق', 'Purchasing approved'),
                        'rejected_by_sales' => l('Sales رفض', 'Rejected by Sales'),
                        'rejected_by_purchasing' => l('Purchasing رفض', 'Rejected by Purchasing'),
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('sales_approve')
                    ->label(l('موافقة Sales', 'Sales Approve'))
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (PurchaseRequest $r) => $r->status === 'pending' && ! $r->isExpired() && auth()->user()->can('update:purchase_request'))
                    ->form([
                        Forms\Components\Textarea::make('notes')->label(l('ملاحظات (اختياري)', 'Notes (optional)'))->rows(2)->maxLength(500),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription(l('سينتقل العرض إلى مراجعة المشتريات وسيتم إخطار المندوب.', 'The offer will move to Purchasing review and the rep will be notified.'))
                    ->action(function (PurchaseRequest $r, array $data) {
                        try {
                            app(PurchaseRequestService::class)->salesApprove($r, Auth::id(), $data['notes'] ?: null);
                            Notification::make()->success()->title(l('تمت موافقة المبيعات', 'Sales approval recorded'))->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
                Action::make('sales_reject')
                    ->label(l('رفض Sales', 'Sales Reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (PurchaseRequest $r) => $r->status === 'pending' && auth()->user()->can('update:purchase_request'))
                    ->form([
                        Forms\Components\Textarea::make('notes')->label(l('سبب الرفض (اختياري)', 'Rejection reason (optional)'))->rows(2)->maxLength(500),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription(l('سيبقى العرض قابلاً للتعديل وإعادة التقديم من المندوب، وسيتم إخطاره بالسبب.', 'The offer stays open for the rep to edit and resubmit; the rep will be notified with the reason.'))
                    ->action(function (PurchaseRequest $r, array $data) {
                        try {
                            app(PurchaseRequestService::class)->salesReject($r, Auth::id(), $data['notes'] ?: null);
                            Notification::make()->success()->title(l('تم تسجيل الرفض', 'Rejection recorded'))->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
                Action::make('purchasing_approve')
                    ->label(l('موافقة Purchasing', 'Purchasing Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PurchaseRequest $r) => $r->status === 'sales_approved' && ! $r->isExpired() && auth()->user()->can('update:purchase_request'))
                    ->form([
                        Forms\Components\Textarea::make('notes')->label(l('ملاحظات (اختياري)', 'Notes (optional)'))->rows(2)->maxLength(500),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription(l('سيتم إنشاء أمر شراء لهذا العرض وإخطار المندوب.', 'A purchase order will be generated for this offer and the rep will be notified.'))
                    ->action(function (PurchaseRequest $r, array $data) {
                        try {
                            $order = app(PurchaseRequestService::class)->purchasingApprove($r, Auth::id(), $data['notes'] ?: null);
                            Notification::make()->success()
                                ->title(l('تم إنشاء أمر الشراء ', 'Purchase order created ').$order->order_number)
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
                Action::make('purchasing_reject')
                    ->label(l('رفض Purchasing', 'Purchasing Reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PurchaseRequest $r) => $r->status === 'sales_approved' && auth()->user()->can('update:purchase_request'))
                    ->form([
                        Forms\Components\Textarea::make('notes')->label(l('سبب الرفض (اختياري)', 'Rejection reason (optional)'))->rows(2)->maxLength(500),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription(l('سيبقى العرض قابلاً للتعديل وإعادة التقديم من المندوب، وسيتم إخطاره بالسبب.', 'The offer stays open for the rep to edit and resubmit; the rep will be notified with the reason.'))
                    ->action(function (PurchaseRequest $r, array $data) {
                        try {
                            app(PurchaseRequestService::class)->purchasingReject($r, Auth::id(), $data['notes'] ?: null);
                            Notification::make()->success()->title(l('تم تسجيل الرفض', 'Rejection recorded'))->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseRequests::route('/'),
            'create' => Pages\CreatePurchaseRequest::route('/create'),
            'edit' => Pages\EditPurchaseRequest::route('/{record}/edit'),
        ];
    }
}
