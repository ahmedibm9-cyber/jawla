<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\CustomerApprovalOutcome;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any:customer') ?? false;
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

        return $schema->schema([
            Section::make(l('البيانات الأساسية', 'Basic Information'))->schema([
                Forms\Components\TextInput::make('name_ar')->label(l('الاسم (عربي)', 'Name (Arabic)'))->required()->maxLength(255),
                Forms\Components\TextInput::make('name_en')->label(l('الاسم (إنجليزي)', 'Name (English)'))->required()->maxLength(255),
                Forms\Components\TextInput::make('code')->label(l('الكود', 'Code'))->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')->label(l('الهاتف', 'Phone'))->tel(),
                Forms\Components\Textarea::make('address')->label(l('العنوان', 'Address'))->columnSpanFull(),
                Forms\Components\Select::make('route_id')->label(l('خط السير', 'Route'))->relationship('route', 'name_ar')->preload(),
                Forms\Components\Select::make('status')->label(l('الحالة', 'Status'))
                    ->options(['pending' => l('قيد الانتظار', 'Pending'), 'approved' => l('معتمد', 'Approved'), 'rejected' => l('مرفوض', 'Rejected')])
                    ->default('approved')->required(),
            ])->columns(3),

            Section::make('GPS')->schema([
                Forms\Components\TextInput::make('latitude')
                    ->label(l('خط العرض', 'Latitude'))
                    ->numeric()
                    ->step(0.0000001)
                    ->minValue(-90)
                    ->maxValue(90),
                Forms\Components\TextInput::make('longitude')
                    ->label(l('خط الطول', 'Longitude'))
                    ->numeric()
                    ->step(0.0000001)
                    ->minValue(-180)
                    ->maxValue(180),
                Forms\Components\Placeholder::make('map')
                    ->label(l('الخريطة', 'Map'))
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
                            <button type="button" @click="if(confirm(\''.l('سيتم استبدال الإحداثيات الحالية بموقعك الحالي. متابعة؟', 'Current coordinates will be replaced with your location. Continue?').'\')) locate()" class="fi-btn fi-btn-sm fi-btn-outline mt-2">'
                        .l('استخدم موقعي الحالي', 'Use my current location')
                        .'</button>
                        </div>'
                    )),
            ])->columns(2),

            Section::make(l('الإعدادات المالية', 'Financial Settings'))->schema([
                Forms\Components\TextInput::make('credit_limit')->label(l('حد الائتمان', 'Credit Limit'))->numeric()->default(0),
                Forms\Components\TextInput::make('balance')->label(l('الرصيد', 'Balance'))->numeric()->default(0)->disabled(),
            ])->columns(2),

            Section::make(l('الفروع والمنافذ', 'Outlets'))->schema([
                Forms\Components\Repeater::make('outlets')->relationship()->schema([
                    Forms\Components\TextInput::make('code')->label(l('الكود', 'Code'))->required()->maxLength(50),
                    Forms\Components\TextInput::make('name_ar')->label(l('الاسم بالعربية', 'Arabic name'))->required()->maxLength(255),
                    Forms\Components\TextInput::make('name_en')->label(l('الاسم بالإنجليزية', 'English name'))->maxLength(255),
                    Forms\Components\TextInput::make('phone')->label(l('الهاتف', 'Phone'))->tel(),
                    Forms\Components\Select::make('route_id')->label(l('خط السير', 'Route'))->relationship('route', 'name_ar')->searchable()->preload(),
                    Forms\Components\Toggle::make('is_active')->label(l('نشط', 'Active'))->default(true),
                ])->columns(2)->defaultItems(0),
            ])->collapsible(),

            Section::make(l('جهات الاتصال', 'Contacts'))->schema([
                Forms\Components\Repeater::make('contacts')->relationship()->schema([
                    Forms\Components\TextInput::make('name')->label(l('الاسم', 'Name'))->required()->maxLength(255),
                    Forms\Components\TextInput::make('job_title')->label(l('المنصب', 'Job title'))->maxLength(255),
                    Forms\Components\TextInput::make('phone')->label(l('الهاتف', 'Phone'))->tel(),
                    Forms\Components\TextInput::make('email')->label(l('البريد الإلكتروني', 'Email'))->email(),
                    Forms\Components\Toggle::make('is_primary')->label(l('جهة الاتصال الأساسية', 'Primary contact')),
                ])->columns(2)->defaultItems(0),
            ])->collapsible(),

            Section::make(l('المواقع', 'Locations'))->schema([
                Forms\Components\Repeater::make('locations')->relationship()->schema([
                    Forms\Components\Select::make('type')->label(l('النوع', 'Type'))->options([
                        'visit' => l('زيارة', 'Visit'), 'billing' => l('فوترة', 'Billing'), 'shipping' => l('شحن', 'Shipping'),
                    ])->default('visit')->required(),
                    Forms\Components\TextInput::make('label')->label(l('التسمية', 'Label'))->required()->maxLength(255),
                    Forms\Components\Textarea::make('address')->label(l('العنوان', 'Address'))->required(),
                    Forms\Components\TextInput::make('latitude')->label(l('خط العرض', 'Latitude'))->numeric()->minValue(-90)->maxValue(90),
                    Forms\Components\TextInput::make('longitude')->label(l('خط الطول', 'Longitude'))->numeric()->minValue(-180)->maxValue(180),
                    Forms\Components\TextInput::make('geofence_radius_m')->label(l('نطاق الوصول بالمتر', 'Geofence radius (m)'))->numeric()->minValue(1),
                    Forms\Components\Toggle::make('is_primary')->label(l('الموقع الأساسي', 'Primary')),
                    Forms\Components\Toggle::make('is_active')->label(l('نشط', 'Active'))->default(true),
                ])->columns(2)->defaultItems(0),
            ])->collapsible(),

            Section::make(l('تعيين المندوبين', 'Rep assignments'))->schema([
                Forms\Components\Repeater::make('assignments')->relationship()->schema([
                    Forms\Components\Select::make('user_id')->label(l('المندوب', 'Representative'))
                        ->relationship('user', 'name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('assignment_type')->label(l('نوع التعيين', 'Assignment type'))->options([
                        'primary' => l('أساسي', 'Primary'), 'support' => l('دعم', 'Support'),
                    ])->default('primary')->required(),
                    Forms\Components\DatePicker::make('starts_on')->label(l('يبدأ في', 'Starts on')),
                    Forms\Components\DatePicker::make('ends_on')->label(l('ينتهي في', 'Ends on'))->afterOrEqual('starts_on'),
                    Forms\Components\Toggle::make('is_active')->label(l('نشط', 'Active'))->default(true),
                    Forms\Components\Hidden::make('assigned_by')->default(fn () => auth()->id()),
                ])->columns(2)->defaultItems(0),
            ])->collapsible(),

            Forms\Components\Toggle::make('is_active')->label(l('نشط', 'Active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label(l('الكود', 'Code'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name_ar')->label(l('الاسم', 'Name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->label(l('الهاتف', 'Phone'))->searchable(),
                Tables\Columns\TextColumn::make('route.name_ar')->label(l('خط السير', 'Route')),
                Tables\Columns\BadgeColumn::make('status')->label(l('الحالة', 'Status'))
                    ->colors(['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger']),
                Tables\Columns\IconColumn::make('is_active')->label(l('نشط', 'Active'))->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(l('الحالة', 'Status'))->options(['pending' => l('قيد الانتظار', 'Pending'), 'approved' => l('معتمد', 'Approved'), 'rejected' => l('مرفوض', 'Rejected')]),
                Tables\Filters\SelectFilter::make('route_id')->label(l('خط السير', 'Route'))->relationship('route', 'name_ar'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Action::make('approve')
                    ->label(l('اعتماد', 'Approve'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Customer $c) => $c->status === 'pending' && $c->added_by !== auth()->id() && auth()->user()->can('update:customer'))
                    ->action(function (Customer $c): void {
                        $c->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        User::find($c->added_by)
                            ?->notify(new CustomerApprovalOutcome($c, 'approved'));
                    })
                    ->requiresConfirmation()
                    ->modalHeading(l('اعتماد العميل', 'Approve customer'))
                    ->modalDescription(l('اعتماد هذا العميل يسمح للمندوبين بإصدار فواتير له، ويُخطر مُنشئ العميل بالاعتماد.', 'Approving this customer lets reps invoice them and notifies whoever added the customer.')),
                Action::make('reject')
                    ->label(l('رفض', 'Reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Customer $c) => $c->status === 'pending' && $c->added_by !== auth()->id() && auth()->user()->can('update:customer'))
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label(l('سبب الرفض', 'Rejection Reason'))
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

                        User::find($c->added_by)
                            ?->notify(new CustomerApprovalOutcome($c, 'rejected', $data['rejection_reason']));
                    })
                    ->requiresConfirmation()
                    ->modalHeading(l('رفض العميل', 'Reject customer'))
                    ->modalDescription(l('رفض هذا العميل يُوقف تنشيطه فلا يمكن إصدار فواتير له، ويُخطر مُنشئه بالسبب. الإجراء مُسجَّل.', 'Rejecting this customer deactivates them so they cannot be invoiced, and notifies whoever added them with your reason. This action is logged.')),
                EditAction::make(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
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
