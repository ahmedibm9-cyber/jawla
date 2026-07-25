<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
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
     * Render widgets in the signed-in user's saved order. Falls back to the
     * panel's default ($sort) ordering; any widget not in the saved order
     * (e.g. newly added) is appended at the end.
     */
    public function getWidgets(): array
    {
        $widgets = parent::getWidgets();
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('customizeDashboard')
                ->label(l('ترتيب اللوحة', 'Customize layout'))
                ->icon('heroicon-o-squares-2x2')
                ->color('gray')
                ->modalHeading(l('ترتيب عناصر اللوحة', 'Reorder dashboard widgets'))
                ->modalDescription(l('اسحب العناصر أو استخدم الأسهم لإعادة ترتيبها على لوحتك.', 'Drag the items or use the arrows to reorder them on your dashboard.'))
                ->modalSubmitActionLabel(l('حفظ', 'Save'))
                ->fillForm(fn (): array => [
                    'widgets' => collect($this->getWidgets())
                        ->map(fn ($widget): array => ['key' => $this->widgetKey($widget)])
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
                        ]),
                ])
                ->action(function (array $data): void {
                    $order = collect($data['widgets'] ?? [])
                        ->pluck('key')
                        ->filter()
                        ->values()
                        ->all();

                    auth()->user()->setPreference('dashboard_widgets', $order);

                    Notification::make()
                        ->title(l('تم حفظ ترتيب اللوحة', 'Dashboard layout saved'))
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
     * Human-friendly label from the widget class basename
     * (e.g. "SalesTodayWidget" => "Sales Today").
     */
    protected function widgetLabel(string $key): string
    {
        if ($key === '') {
            return '';
        }

        $label = Str::of(class_basename($key))->beforeLast('Widget')->headline()->toString();

        return $label !== '' ? $label : class_basename($key);
    }
}
