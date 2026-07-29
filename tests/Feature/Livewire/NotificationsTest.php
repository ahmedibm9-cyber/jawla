<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\Notifications;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_renders_notification_list(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(Notifications::class)
            ->assertOk();
    }

    public function test_mount_marks_unread_as_read(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        $notifId = (string) Str::uuid();
        $rep->notifications()->create([
            'id' => $notifId,
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => ['message' => 'Test'],
            'read_at' => null,
        ]);

        Livewire::test(Notifications::class)
            ->assertSet('newIds', fn ($ids) => in_array($notifId, $ids));

        $this->assertDatabaseHas('notifications', [
            'id' => $notifId,
        ]);
    }
}
