<?php

use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Middleware\SetActiveCompanyContext;
use App\Support\ApiAbilities;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

/*
| Public API v1
|
| Every route requires a valid Sanctum token (auth:sanctum), is throttled by the
| 'api' limiter, and runs under the token owner's company scope
| (SetActiveCompanyContext reads $request->user()). Data endpoints additionally
| require the matching ability; /whoami needs only a valid token.
*/
Route::prefix('v1')
    ->middleware(['auth:sanctum', SetActiveCompanyContext::class, 'throttle:api'])
    ->group(function () {
        Route::get('whoami', [MetaController::class, 'whoami']);

        Route::get('products', [ProductController::class, 'index'])
            ->middleware(CheckForAnyAbility::class.':'.ApiAbilities::READ_PRODUCTS);

        Route::get('customers', [CustomerController::class, 'index'])
            ->middleware(CheckForAnyAbility::class.':'.ApiAbilities::READ_CUSTOMERS);
    });
