<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class CustomerResource extends Resource
{
    public static function getModel(): string
    {
        return Customer::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-user-group';
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales';
    }

    public static function getLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'عميل' : 'Customer';
    }

    public static function getPluralLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'العملاء' : 'Customers';
    }

    public static function form(Schema $schema): Schema
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $schema->schema([
            Forms\Components\Section::make($l('البيانات الأساسية', 'Basic Information'))->schema([
                Forms\Components\TextInput::make('name_ar')->label($l('الاسم (عربي)', 'Name (Arabic)'))->required()->maxLength(255),
                Forms\Components\TextInput::make('name_en')->label($l('الاسم (إنجليزي)', 'Name (English)'))->required()->maxLength(255),
                Forms\Components\TextInput::make('code')->label($l('الكود', 'Code'))->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')->label($l('الهاتف', 'Phone'))->tel(),
                Forms\Components\Textarea::make('address')->label($l('العنوان', 'Address'))->columnSpanFull(),
                Forms\Components\Select::make('route_id')->label($l('خط السير', 'Route'))->relationship('route', 'name_ar')->preload(),
                Forms\Components\Select::make('status')->label($l('الحالة', 'Status'))
                    ->options(['pending' => $l('قيد الانتظار', 'Pending'), 'approved' => $l('معتمد', 'Approved'), 'rejected' => $l('مرفوض', 'Rejected')])
                    ->default('approved')->required(),
            ])->columns(3),

            Forms\Components\Section::make('GPS')->schema([
                Forms\Components\TextInput::make('latitude')
                    ->label($l('خط العرض', 'Latitude'))
                    ->numeric()
                    ->step(0.0000001)
                    ->minValue(-90)
                    ->maxValue(90),
                Forms\Components\TextInput::make('longitude')
                    ->label($l('خط الطول', 'Longitude'))
                    ->numeric()
                    ->step(0.0000001)
                    ->minValue(-180)
                    ->maxValue(180),
                Forms\Components\Placeholder::make('map')
                    ->label($l('الخريطة', 'Map'))
                    ->content(new HtmlString(
                        '<div x-data="{
                            lat: $wire.data.latitude || 30.0444,
                            lng: $wire.data.longitude || 31.2357,
                            map: null,
                            marker: null,
                            init() {
                                if (typeof L === \'undefined\') return;
                                this.map = L.map(this.$el.querySelector(\'[data-leaflet]\')).setView([this.lat, this.lng], 13);
                                L.tileLayer(\'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png\', {
                                    attribution: \'&copy; OpenStreetMap\'
                                }).addTo(this.map);
                                this.marker = L.marker([this.lat, this.lng], { draggable: true }).addTo(this.map);
                                this.marker.on(\'dragend\', (e) => {
                                    const pos = e.target.getLatLng();
                                    $wire.data.latitude = pos.lat.toFixed(6);
                                    $wire.data.longitude = pos.lng.toFixed(6);
                                });
                                this.map.on(\'click\', (e) => {
                                    this.marker.setLatLng(e.latlng);
                                    $wire.data.latitude = e.latlng.lat.toFixed(6);
                                    $wire.data.longitude = e.latlng.lng.toFixed(6);
                                });
                            },
                            locate() {
                                if (!navigator.geolocation) return;
                                navigator.geolocation.getCurrentPosition((pos) => {
                                    const lat = pos.coords.latitude;
                                    const lng = pos.coords.longitude;
                                    $wire.data.latitude = lat.toFixed(6);
                                    $wire.data.longitude = lng.toFixed(6);
                                    this.marker.setLatLng([lat, lng]);
                                    this.map.setView([lat, lng], 15);
                                });
                            }
                        }" x-init="init()">
                            <div data-leaflet style="width:100%;height:300px;border-radius:8px;border:1px solid #d1d5db"></div>
                            <button type="button" @click="locate()" class="fi-btn fi-btn-sm fi-btn-outline mt-2">'
                        .$l('استخدم موقعي الحالي', 'Use my current location')
                        .'</button>
                        </div>'
                    )),
            ])->columns(2),

            Forms\Components\Section::make($l('الإعدادات المالية', 'Financial Settings'))->schema([
                Forms\Components\TextInput::make('credit_limit')->label($l('حد الائتمان', 'Credit Limit'))->numeric()->default(0),
                Forms\Components\TextInput::make('balance')->label($l('الرصيد', 'Balance'))->numeric()->default(0)->disabled(),
            ])->columns(2),

            Forms\Components\Toggle::make('is_active')->label($l('نشط', 'Active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        $l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label($l('الكود', 'Code'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name_ar')->label($l('الاسم', 'Name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->label($l('الهاتف', 'Phone'))->searchable(),
                Tables\Columns\TextColumn::make('route.name_ar')->label($l('خط السير', 'Route')),
                Tables\Columns\BadgeColumn::make('status')->label($l('الحالة', 'Status'))
                    ->colors(['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger']),
                Tables\Columns\IconColumn::make('is_active')->label($l('نشط', 'Active'))->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label($l('الحالة', 'Status'))->options(['pending' => $l('قيد الانتظار', 'Pending'), 'approved' => $l('معتمد', 'Approved'), 'rejected' => $l('مرفوض', 'Rejected')]),
                Tables\Filters\SelectFilter::make('route_id')->label($l('خط السير', 'Route'))->relationship('route', 'name_ar'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Filament\Actions\Action::make('approve')
                    ->label($l('اعتماد', 'Approve'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Customer $c) => $c->status === 'pending' && $c->added_by !== auth()->id())
                    ->action(fn (Customer $c) => $c->update([
                        'status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]))
                    ->requiresConfirmation(),
                Filament\Actions\Action::make('reject')
                    ->label($l('رفض', 'Reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Customer $c) => $c->status === 'pending' && $c->added_by !== auth()->id())
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label($l('سبب الرفض', 'Rejection Reason'))
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Customer $c, array $data) {
                        $c->update([
                            'status' => 'rejected',
                            'is_active' => false,
                            'rejected_by' => auth()->id(),
                            'rejected_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                    })
                    ->requiresConfirmation(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
