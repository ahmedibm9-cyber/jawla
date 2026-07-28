<?php

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;

function makeRepForSync(): User
{
    test()->seed(RoleSeeder::class);
    $company = Company::factory()->create(['name_ar' => 'شركة زوار']);
    $rep = User::factory()->create(['company_id' => $company->id, 'name' => 'مندوب زوار']);
    $rep->assignRole('rep');

    return $rep;
}

it('loads the sync queue page without JavaScript errors', function () {
    $rep = makeRepForSync();

    $page = $this->actingAs($rep)->visit('/app/sync-queue');

    $page->assertNoJavascriptErrors()
        ->assertSee(__('app.sync_queue'));
});

it('shows the empty sync state when nothing is pending', function () {
    $rep = makeRepForSync();

    $page = $this->actingAs($rep)->visit('/app/sync-queue');

    $page->assertNoJavascriptErrors()
        ->assertSee(__('app.sync_all_synced'));
});

it('renders sync queue in Arabic RTL', function () {
    $rep = makeRepForSync();

    $page = $this->withSession(['locale' => 'ar'])->actingAs($rep)->visit('/app/sync-queue');

    $page->assertNoJavascriptErrors()
        ->assertSourceHas('dir="rtl"');
});
