<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

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
}
