<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PhotoService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * B2 — photo storage disk is config-driven so production can use the durable
 * Railway bucket (s3) while local/tests use 'public'. The disk is recorded per
 * row, so the cutover is gradual and delete() still resolves old photos.
 */
class PhotoDiskConfigTest extends TestCase
{
    use RefreshDatabase;

    private User $rep;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        $this->rep = User::where('email', 'rep@jawla.test')->firstOrFail();
        app(ActiveCompanyContext::class)->setFromUser($this->rep);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    public function test_defaults_to_public_disk(): void
    {
        Storage::fake('public');
        $photo = app(PhotoService::class)->store(UploadedFile::fake()->image('e.jpg'), $this->rep);

        $this->assertSame('public', $photo->disk);
        Storage::disk('public')->assertExists($photo->path);
    }

    public function test_uses_configured_photo_disk_when_set(): void
    {
        config(['filesystems.photo_disk' => 's3']);
        Storage::fake('s3');

        $photo = app(PhotoService::class)->store(UploadedFile::fake()->image('e.jpg'), $this->rep);

        $this->assertSame('s3', $photo->disk);
        Storage::disk('s3')->assertExists($photo->path);
    }

    public function test_metadata_is_removed_before_uploading_to_object_storage(): void
    {
        config(['filesystems.photo_disk' => 's3']);
        Storage::fake('s3');

        $upload = UploadedFile::fake()->image('gps.jpg', 20, 20);
        $jpeg = file_get_contents($upload->getRealPath());
        $metadata = "Exif\0\0GPSLatitude=30.0444;GPSLongitude=31.2357";
        $segment = "\xFF\xE1".pack('n', strlen($metadata) + 2).$metadata;
        file_put_contents($upload->getRealPath(), substr($jpeg, 0, 2).$segment.substr($jpeg, 2));

        $photo = app(PhotoService::class)->store($upload, $this->rep);
        $stored = Storage::disk('s3')->get($photo->path);

        $this->assertStringNotContainsString("Exif\0\0", $stored);
        $this->assertStringNotContainsString('GPSLatitude', $stored);
        $this->assertNotFalse(imagecreatefromstring($stored));
    }

    public function test_delete_resolves_the_rows_own_disk(): void
    {
        config(['filesystems.photo_disk' => 's3']);
        Storage::fake('s3');
        $photo = app(PhotoService::class)->store(UploadedFile::fake()->image('e.jpg'), $this->rep);

        app(PhotoService::class)->delete($photo);

        Storage::disk('s3')->assertMissing($photo->path);
        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
    }
}
