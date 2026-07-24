<?php

namespace App\Filament\Resources;

use App\Enums\VanTransferStatus;
use App\Filament\Resources\VanTransferResource\Pages;
use App\Models\VanTransfer;
use App\Models\Warehouse;
use App\Services\Contracts\VanTransferService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class VanTransferResource extends Resource
{
    public static function getModel(): string
    {
        return VanTransfer::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-truck';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المخزون' : 'Inventory';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'تحويل مخزون' : 'Van Transfer';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'تحويلات المخزون' : 'Van Transfers';
    }

    public static function form(Schema $schema): Schema
    {

        return $schema->schema([
            Section::make(l('بيانات التحويل', 'Transfer Info'))->schema([
                Forms\Components\Select::make('from_user_id')
                    ->label(l('من مستخدم', 'From User'))
                    ->relationship('fromUser', 'name')
                    ->required(),
                Forms\Components\Select::make('to_user_id')
                    ->label(l('إلى مستخدم', 'To User'))
                    ->relationship('toUser', 'name')
                    ->required(),
                Forms\Components\Select::make('in_transit_warehouse_id')
                    ->label(l('مستودع العبور', 'In-Transit Warehouse'))
                    ->relationship('transitWarehouse', 'name_ar'),
                Forms\Components\TextInput::make('items_count')
                    ->label(l('عدد الأصناف', 'Items'))
                    ->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fromUser.name')
                    ->label(l('من', 'From'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('toUser.name')
                    ->label(l('إلى', 'To'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label(l('الأصناف', 'Items'))
                    ->counts('items'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label(l('الحالة', 'Status'))
                    ->colors([
                        'pending' => 'warning',
                        'accepted' => 'info',
                        'shipped' => 'primary',
                        'received' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(l('التاريخ', 'Date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(l('الحالة', 'Status'))
                    ->options(collect(VanTransferStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->value])),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('ship')
                    ->label(l('شحن', 'Ship'))
                    ->icon('heroicon-o-rocket-launch')
                    ->color('primary')
                    ->visible(fn (VanTransfer $r) => $r->status === VanTransferStatus::Pending)
                    ->requiresConfirmation()
                    ->modalDescription(fn () => l('سيتم خصم المخزون من المستودع الرئيسي.', 'Stock will be deducted from the main warehouse.'))
                    ->form([
                        Forms\Components\Select::make('from_warehouse_id')
                            ->label(fn () => l('المستودع المصدر', 'Source Warehouse'))
                            ->options(fn () => Warehouse::where('type', 'main')->pluck('name_ar', 'id'))
                            ->required(),
                    ])
                    ->action(function (VanTransfer $record, array $data) {
                        app(VanTransferService::class)->ship(
                            $record->id,
                            (int) $data['from_warehouse_id'],
                            auth()->id(),
                        );

                        Notification::make()->title(l('تم الشحن', 'Shipped'))->success()->send();
                    }),
                Action::make('receive')
                    ->label(l('استلام', 'Receive'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (VanTransfer $r) => $r->status === VanTransferStatus::Shipped)
                    ->requiresConfirmation()
                    ->modalDescription(fn () => l('سيتم إضافة المخزون إلى مخزن المستخدم المستلم.', 'Stock will be added to the receiving user\'s van warehouse.'))
                    ->action(function (VanTransfer $record) {
                        app(VanTransferService::class)->receive(
                            $record->id,
                            auth()->id(),
                        );

                        Notification::make()->title(l('تم الاستلام', 'Received'))->success()->send();
                    }),
                Action::make('reject')
                    ->label(l('رفض', 'Reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (VanTransfer $r) => in_array($r->status, [VanTransferStatus::Pending, VanTransferStatus::Accepted]))
                    ->requiresConfirmation()
                    ->action(function (VanTransfer $record) {
                        app(VanTransferService::class)->reject(
                            $record->id,
                            auth()->id(),
                        );

                        Notification::make()->title(l('تم الرفض', 'Rejected'))->success()->send();
                    }),
                Action::make('cancel')
                    ->label(l('إلغاء', 'Cancel'))
                    ->icon('heroicon-o-ban')
                    ->color('gray')
                    ->visible(fn (VanTransfer $r) => $r->status === VanTransferStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function (VanTransfer $record) {
                        app(VanTransferService::class)->cancel(
                            $record->id,
                            auth()->id(),
                        );

                        Notification::make()->title(l('تم الإلغاء', 'Cancelled'))->success()->send();
                    }),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVanTransfers::route('/'),
        ];
    }
}
