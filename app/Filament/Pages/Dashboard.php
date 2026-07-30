<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Str;

class Dashboard extends BaseDashboard
{
    protected static string $routePath = '/dashboard';

    public function getColumns(): int|array
    {
        return [
            'default' => 3,
            'md' => 3,
            'lg' => 3,
        ];
    }

    /**
     * Render widgets in the signed-in user's saved order and visibility.
     * New widgets are appended and visible by default, so an old preference
     * can never silently hide a newly released dashboard capability.
     */
    public function getWidgets(): array
    {
        $widgets = $this->orderedWidgets($this->availableWidgets());
        $hidden = $this->hiddenWidgetKeys();

        return array_values(array_filter(
            $widgets,
            fn (mixed $widget): bool => ! in_array($this->widgetKey($widget), $hidden, true),
        ));
    }

    /** @return array<int, mixed> */
    protected function availableWidgets(): array
    {
        return parent::getWidgets();
    }

    /** @param array<int, mixed> $widgets */
    protected function orderedWidgets(array $widgets): array
    {
        $order = auth()->user()?->preference('dashboard_widgets');

        if (empty($order) || ! is_array($order)) {
            return $widgets;
        }

        return collect($widgets)
            ->sortBy(function ($widget) use ($order): int {
                $index = array_search($this->widgetKey($widget), $order, true);

                return $index === false ? PHP_INT_MAX : $index;
            })
            ->values()
            ->all();
    }

    /** @return list<string> */
    protected function hiddenWidgetKeys(): array
    {
        $hidden = auth()->user()?->preference('dashboard_hidden_widgets', []);

        if (! is_array($hidden)) {
            return [];
        }

        $available = collect($this->availableWidgets())
            ->map(fn (mixed $widget): string => $this->widgetKey($widget))
            ->all();

        return array_values(array_filter(
            $hidden,
            fn (mixed $key): bool => is_string($key) && in_array($key, $available, true),
        ));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('customizeDashboard')
                ->label(l('تخصيص اللوحة', 'Customize dashboard'))
                ->icon('heroicon-o-squares-2x2')
                ->color('gray')
                ->modalHeading(l('تخصيص عناصر اللوحة', 'Customize dashboard widgets'))
                ->modalDescription(l('اسحب العناصر أو استخدم الأسهم لإعادة ترتيبها، وأوقف إظهار أي عنصر لإزالته من لوحتك. يمكنك إظهاره مجدداً في أي وقت.', 'Drag the items or use the arrows to reorder them. Turn off any widget to remove it from your dashboard; you can re-enable it at any time.'))
                ->modalSubmitActionLabel(l('حفظ', 'Save'))
                ->fillForm(fn (): array => [
                    'widgets' => collect($this->orderedWidgets($this->availableWidgets()))
                        ->map(fn (mixed $widget): array => [
                            'key' => $this->widgetKey($widget),
                            'visible' => ! in_array($this->widgetKey($widget), $this->hiddenWidgetKeys(), true),
                        ])
                        ->all(),
                ])
                ->form([
                    Repeater::make('widgets')
                        ->hiddenLabel()
                        ->reorderable()
                        ->reorderableWithButtons()
                        ->addable(false)
                        ->deletable(false)
                        ->collapsible(false)
                        ->itemLabel(fn (array $state): ?string => $this->widgetLabel($state['key'] ?? ''))
                        ->schema([
                            Hidden::make('key'),
                            Toggle::make('visible')
                                ->label(l('إظهار في لوحة التحكم', 'Show on dashboard'))
                                ->default(true),
                        ]),
                ])
                ->action(function (array $data): void {
                    $available = collect($this->availableWidgets())
                        ->map(fn (mixed $widget): string => $this->widgetKey($widget))
                        ->all();
                    $configured = collect($data['widgets'] ?? [])
                        ->filter(fn (mixed $widget): bool => is_array($widget)
                            && isset($widget['key'])
                            && is_string($widget['key'])
                            && in_array($widget['key'], $available, true))
                        ->unique('key')
                        ->values();
                    $order = $configured->pluck('key')->all();

                    foreach ($available as $key) {
                        if (! in_array($key, $order, true)) {
                            $order[] = $key;
                        }
                    }

                    $hidden = $configured
                        ->filter(fn (array $widget): bool => ! ($widget['visible'] ?? true))
                        ->pluck('key')
                        ->all();

                    auth()->user()->setPreference('dashboard_widgets', $order);
                    auth()->user()->setPreference('dashboard_hidden_widgets', $hidden);

                    Notification::make()
                        ->title(l('تم حفظ تخصيص اللوحة', 'Dashboard customization saved'))
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * Stable identifier for a widget entry (a class-string, or a
     * WidgetConfiguration wrapping a ->widget class).
     */
    protected function widgetKey(mixed $widget): string
    {
        if (is_string($widget)) {
            return $widget;
        }

        if (is_object($widget)) {
            return $widget->widget ?? $widget::class;
        }

        return (string) $widget;
    }

    /**
     * Human-friendly bilingual labels for each dashboard widget.
     */
    protected function widgetLabel(string $key): string
    {
        if ($key === '') {
            return '';
        }

        $labels = [
            'AccountWidget' => l('حسابي', 'My account'),
            'VisitsTodayWidget' => l('زيارات اليوم', 'Today\'s visits'),
            'PendingQuotationsWidget' => l('عروض الأسعار قيد الانتظار', 'Pending quotations'),
            'OpenAlarmsWidget' => l('التنبيهات غير المقروءة', 'Unread alerts'),
            'SalesTodayWidget' => l('مبيعات اليوم', 'Today\'s sales'),
            'OutstandingBalanceWidget' => l('الرصيد المستحق', 'Outstanding balance'),
            'LowStockAlertWidget' => l('تنبيهات المخزون', 'Stock alerts'),
            'RepPerformanceWidget' => l('أداء المندوبين', 'Rep performance'),
        ];

        $basename = class_basename($key);

        if (isset($labels[$basename])) {
            return $labels[$basename];
        }

        $label = Str::of($basename)->beforeLast('Widget')->headline()->toString();

        return $label !== '' ? $label : $basename;
    }
}
