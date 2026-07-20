<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\SyncServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    AuthServiceProvider::class,
    SyncServiceProvider::class,
];
