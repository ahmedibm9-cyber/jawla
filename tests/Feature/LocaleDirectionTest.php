<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleDirectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::where('email', 'rep@jawla.test')->firstOrFail());
    }

    public function test_arabic_session_renders_rtl_dir_attribute(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/app');
        $response->assertStatus(200);
        $this->assertStringContainsString('dir="rtl"', $response->getContent());
    }

    public function test_english_session_renders_ltr_dir_attribute(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get('/app');
        $response->assertStatus(200);
        $this->assertStringContainsString('dir="ltr"', $response->getContent());
    }
}
