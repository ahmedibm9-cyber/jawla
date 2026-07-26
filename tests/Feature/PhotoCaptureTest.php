<?php

namespace Tests\Feature;

use App\Livewire\App\PhotoCapture;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Photo;
use App\Models\User;
use App\Services\PhotoService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Wave-1 reusable photo capture: PhotoService storage/attach/delete and the
 * PhotoCapture Livewire widget (upload, validation, event, removal).
 */
class PhotoCaptureTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->rep->assignRole('rep');
        app(ActiveCompanyContext::class)->setFromUser($this->rep);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    public function test_service_stores_file_and_row(): void
    {
        $photo = app(PhotoService::class)->store(UploadedFile::fake()->image('visit.jpg'), $this->rep);

        $this->assertDatabaseHas('photos', [
            'id' => $photo->id,
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
        ]);
        Storage::disk('public')->assertExists($photo->path);
    }

    public function test_service_attaches_to_a_parent_record(): void
    {
        $photo = app(PhotoService::class)->store(UploadedFile::fake()->image('a.jpg'), $this->rep);
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'route_id' => null,
        ]);

        app(PhotoService::class)->attach($photo, $customer);

        $this->assertDatabaseHas('photos', [
            'id' => $photo->id,
            'photable_type' => $customer->getMorphClass(),
            'photable_id' => $customer->id,
        ]);
    }

    public function test_service_delete_removes_file_and_row(): void
    {
        $photo = app(PhotoService::class)->store(UploadedFile::fake()->image('b.jpg'), $this->rep);
        $path = $photo->path;

        app(PhotoService::class)->delete($photo);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
    }

    public function test_component_uploads_image_and_emits_event(): void
    {
        $component = Livewire::actingAs($this->rep)
            ->test(PhotoCapture::class)
            ->set('photo', UploadedFile::fake()->image('c.jpg'))
            ->assertDispatched('photo-captured');

        $this->assertCount(1, $component->get('stored'));
        $this->assertDatabaseCount('photos', 1);
    }

    public function test_component_rejects_non_image(): void
    {
        Livewire::actingAs($this->rep)
            ->test(PhotoCapture::class)
            ->set('photo', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))
            ->assertHasErrors(['photo']);

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_component_can_remove_a_captured_photo(): void
    {
        $component = Livewire::actingAs($this->rep)
            ->test(PhotoCapture::class)
            ->set('photo', UploadedFile::fake()->image('d.jpg'));

        $id = $component->get('stored')[0]['id'];

        $component->call('removePhoto', $id)->assertDispatched('photo-removed');

        $this->assertDatabaseMissing('photos', ['id' => $id]);
        $this->assertCount(0, $component->get('stored'));
    }

    public function test_photos_are_company_scoped(): void
    {
        app(PhotoService::class)->store(UploadedFile::fake()->image('mine.jpg'), $this->rep);

        $other = Company::factory()->create();
        $otherRep = User::factory()->create(['company_id' => $other->id]);
        app(ActiveCompanyContext::class)->runWithCompany(
            $other->id,
            fn () => Photo::factory()->create([
                'company_id' => $other->id,
                'user_id' => $otherRep->id,
            ]),
        );

        // Under the rep's active-company scope, only own-company photos are visible.
        $this->assertSame(1, Photo::count());
    }
}
