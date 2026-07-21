<?php

namespace Tests\Feature;

use App\Livewire\App\LogComplaint;
use App\Livewire\App\LogReturn;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Photo;
use App\Models\Product;
use App\Models\ReturnRecord;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 2 — photo wire-in. The nested photo-capture widget stores each image and
 * dispatches photo-captured; the host flow collects the ids and attaches them to
 * its record on save (server-side photable). These tests prove the collect →
 * attach contract for the complaint and return flows.
 */
class PhotoWireInTest extends TestCase
{
    use RefreshDatabase;

    private User $rep;

    private Customer $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        $this->rep = User::where('email', 'rep@jawla.test')->firstOrFail();
        $this->customer = Customer::where('status', 'approved')->firstOrFail();
        $this->product = Product::where('sku', 'PP-H030')->firstOrFail();
        $this->actingAs($this->rep);
        app(ActiveCompanyContext::class)->setFromUser($this->rep);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    /** A standalone, not-yet-attached photo owned by the rep (as the widget stores it). */
    private function orphanPhoto(): Photo
    {
        return Photo::create([
            'company_id' => $this->rep->company_id,
            'user_id' => $this->rep->id,
            'disk' => 'public',
            'path' => 'photos/'.Str::uuid().'.jpg',
            'original_name' => 'evidence.jpg',
            'size' => 12345,
        ]);
    }

    public function test_complaint_attaches_captured_photos_on_save(): void
    {
        $photo = $this->orphanPhoto();

        Livewire::test(LogComplaint::class)
            ->call('addPhoto', $photo->id)
            ->set('customer_id', $this->customer->id)
            ->set('complaint_type', 'quality_issue')
            ->set('description', 'Damaged goods on arrival')
            ->call('submit');

        $complaint = Complaint::query()->latest('id')->firstOrFail();
        $fresh = $photo->fresh();
        $this->assertSame($complaint->getMorphClass(), $fresh->photable_type);
        $this->assertSame($complaint->id, $fresh->photable_id);
    }

    public function test_return_attaches_captured_photos_on_save(): void
    {
        $photo = $this->orphanPhoto();

        Livewire::test(LogReturn::class)
            ->call('addPhoto', $photo->id)
            ->set('customer_id', $this->customer->id)
            ->set('items.0.product_id', $this->product->id)
            ->set('items.0.quantity', 1)
            ->set('items.0.unit_price', 10)
            ->call('submit');

        $return = ReturnRecord::query()->latest('id')->firstOrFail();
        $fresh = $photo->fresh();
        $this->assertSame($return->getMorphClass(), $fresh->photable_type);
        $this->assertSame($return->id, $fresh->photable_id);
    }

    public function test_removed_photo_is_not_attached(): void
    {
        $photo = $this->orphanPhoto();

        Livewire::test(LogComplaint::class)
            ->call('addPhoto', $photo->id)
            ->call('dropPhoto', $photo->id)
            ->set('customer_id', $this->customer->id)
            ->set('complaint_type', 'other')
            ->set('description', 'No photo after all')
            ->call('submit');

        $this->assertNull($photo->fresh()->photable_id);
    }
}
