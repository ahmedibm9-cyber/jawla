<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Per-user admin preferences. Currently lets an admin reorder the sidebar
 * navigation sections (groups). The chosen order is stored on the user and
 * applied to the panel at serve time (see AdminPanelProvider::boot).
 */
class AdminPreferences extends Page
{
    protected string $view = 'filament.pages.admin-preferences';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    /** @var list<string> Navigation group names in the user's chosen order. */
    public array $order = [];

    public static function getNavigationLabel(): string
    {
        return l('تخصيص الواجهة', 'Customize interface');
    }

    public function getTitle(): string
    {
        return self::getNavigationLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return l('الإعدادات', 'Settings');
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);

        $available = $this->availableGroups();
        $saved = (array) (Auth::user()?->preference('nav_group_order', []) ?? []);

        // Keep saved groups that still exist (in saved order), then append any
        // groups that appeared since (so new sections are never hidden).
        $ordered = array_values(array_filter($saved, fn ($g): bool => in_array($g, $available, true)));

        foreach ($available as $group) {
            if (! in_array($group, $ordered, true)) {
                $ordered[] = $group;
            }
        }

        $this->order = $ordered;
    }

    public function moveUp(int $index): void
    {
        if ($index > 0 && isset($this->order[$index])) {
            [$this->order[$index - 1], $this->order[$index]] = [$this->order[$index], $this->order[$index - 1]];
        }
    }

    public function moveDown(int $index): void
    {
        if ($index < count($this->order) - 1 && isset($this->order[$index])) {
            [$this->order[$index + 1], $this->order[$index]] = [$this->order[$index], $this->order[$index + 1]];
        }
    }

    public function save(): void
    {
        abort_unless(self::canAccess(), 403);

        Auth::user()->setPreference('nav_group_order', array_values($this->order));

        Notification::make()
            ->success()
            ->title(l('تم حفظ ترتيب القائمة', 'Sidebar order saved'))
            ->body(l('حدّث الصفحة لرؤية الترتيب الجديد.', 'Refresh to see the new order.'))
            ->send();
    }

    public function resetOrder(): void
    {
        abort_unless(self::canAccess(), 403);

        Auth::user()->setPreference('nav_group_order', []);
        $this->order = $this->availableGroups();

        Notification::make()
            ->success()
            ->title(l('تمت إعادة الترتيب الافتراضي', 'Order reset to default'))
            ->send();
    }

    /**
     * Distinct sidebar section (navigation group) names in the admin panel.
     *
     * @return list<string>
     */
    protected function availableGroups(): array
    {
        $panel = Filament::getPanel('admin');
        $groups = [];

        $collect = function (string $class) use (&$groups): void {
            if (! method_exists($class, 'getNavigationGroup')) {
                return;
            }
            $group = $class::getNavigationGroup();
            if (is_string($group) && $group !== '') {
                $groups[$group] = true;
            }
        };

        foreach ($panel->getResources() as $resource) {
            $collect($resource);
        }
        foreach ($panel->getPages() as $page) {
            $collect($page);
        }

        return array_keys($groups);
    }
}
