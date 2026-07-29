<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Route;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitReport;
use App\Models\WorkSession;
use App\Services\PhotoService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    app(ActiveCompanyContext::class)->setCompanyId($this->company->id);
});

test('store creates photo record', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

    $photo = app(PhotoService::class)->store($file, $this->user);

    $this->assertNotNull($photo->id);
    $this->assertSame($this->company->id, $photo->company_id);
    $this->assertSame($this->user->id, $photo->user_id);
    $this->assertSame('public', $photo->disk);
    $this->assertNotEmpty($photo->path);
    $this->assertSame('photo.jpg', $photo->original_name);
    $this->assertGreaterThan(0, $photo->size);
});

test('store stores file on disk', function () {
    $file = UploadedFile::fake()->image('test.png', 640, 480);

    $photo = app(PhotoService::class)->store($file, $this->user);

    Storage::disk('public')->assertExists($photo->path);
});

test('attach links to model', function () {
    $file = UploadedFile::fake()->image('attach.jpg', 800, 600);
    $photo = app(PhotoService::class)->store($file, $this->user);

    $route = Route::factory()->create(['company_id' => $this->company->id]);
    $customer = Customer::factory()->create(['company_id' => $this->company->id, 'route_id' => $route->id]);
    $ws = WorkSession::factory()->create(['user_id' => $this->user->id, 'company_id' => $this->company->id, 'route_id' => $route->id]);
    $visit = Visit::factory()->create([
        'user_id' => $this->user->id,
        'customer_id' => $customer->id,
        'route_id' => $route->id,
        'work_session_id' => $ws->id,
        'checkin_latitude' => 30.0,
        'checkin_longitude' => 31.0,
    ]);
    $visitReport = VisitReport::factory()->create(['visit_id' => $visit->id]);

    $result = app(PhotoService::class)->attach($photo, $visitReport);

    $this->assertSame(get_class($visitReport), $result->photable_type);
    $this->assertSame($visitReport->id, $result->photable_id);
});

test('delete removes file and record', function () {
    $file = UploadedFile::fake()->image('delete.jpg', 800, 600);
    $photo = app(PhotoService::class)->store($file, $this->user);
    $path = $photo->path;

    app(PhotoService::class)->delete($photo);

    Storage::disk('public')->assertMissing($path);
    $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
});
