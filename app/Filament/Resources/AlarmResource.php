<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlarmResource\Pages;
use App\Models\Alarm;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AlarmResource extends Resource
{
    protected static ?string $model = Alarm::class;

    public static function getModel(): string { return Alarm::class; }
    public static function getNavigationIcon(): string { return 'heroicon-o-bell-alert'; }
    public static function getNavigationGroup(): ?string { return app()->getLocale() === 'ar' ? 'التنبيهات' : 'Alarms'; }
    public static function getLabel(): string { return app()->getLocale() === 'ar' ? 'تنبيه' : 'Alarm'; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('type')->label(app()->getLocale()==='ar'?'النوع':'Type')
                    ->colors(['out_of_stock_request'=>'danger','customer_complaint'=>'danger','new_customer_pending'=>'warning','price_quotation_requested'=>'info','purchase_request'=>'success','goods_in_transit_delayed'=>'warning','batch_expiring'=>'warning']),
                Tables\Columns\BadgeColumn::make('severity')->label(app()->getLocale()==='ar'?'المستوى':'Severity')
                    ->colors(['critical'=>'danger','warning'=>'warning','info'=>'primary']),
                Tables\Columns\TextColumn::make('title')->label(app()->getLocale()==='ar'?'العنوان':'Title')->searchable(),
                Tables\Columns\TextColumn::make('description')->label(app()->getLocale()==='ar'?'الوصف':'Description')->limit(60),
                Tables\Columns\IconColumn::make('is_read')->label(app()->getLocale()==='ar'?'مقروء':'Read')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label(app()->getLocale()==='ar'?'التاريخ':'Date')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([Tables\Actions\Action::make('mark_read')->label(app()->getLocale()==='ar'?'تحديد كمقروء':'Mark Read')->action(fn(Alarm $a)=>$a->update(['is_read'=>true,'read_by'=>auth()->id(),'read_at'=>now()]))]);
    }

    public static function getPages(): array { return ['index'=>Pages\ListAlarms::route('/')]; }
}