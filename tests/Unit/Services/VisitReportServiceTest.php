<?php

namespace Tests\Unit\Services;

use App\Enums\VisitStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Models\Visit;
use App\Models\WorkSession;
use App\Services\VisitReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisitReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createVisit(Company $company, User $rep): Visit
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $workSession = WorkSession::factory()->create([
            'user_id' => $rep->id,
            'company_id' => $company->id,
        ]);

        return Visit::factory()->create([
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'work_session_id' => $workSession->id,
            'status' => 'open',
            'arrival_confirmed' => true,
        ]);
    }

    public function test_submit_creates_report_and_closes_visit(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $visit = $this->createVisit($company, $rep);

        $report = app(VisitReportService::class)->submit($visit, [
            'summary' => 'Met with purchasing manager, discussed Q3 order schedule',
            'customer_feedback' => 'Happy with service',
            'action_taken' => 'Left product samples',
            'follow_up_needed' => true,
            'follow_up_note' => 'Call next week for decision',
        ]);

        $this->assertSame('Met with purchasing manager, discussed Q3 order schedule', $report->summary);
        $this->assertSame('Happy with service', $report->customer_feedback);
        $this->assertTrue($report->follow_up_needed);
        $this->assertSame(VisitStatus::Closed, $visit->fresh()->status);
    }

    public function test_submit_closes_the_visit(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $visit = $this->createVisit($company, $rep);

        app(VisitReportService::class)->submit($visit, [
            'summary' => 'Quick check-in',
        ]);

        $this->assertSame(VisitStatus::Closed, $visit->fresh()->status);
    }

    public function test_submit_saves_signature_when_provided(): void
    {
        Storage::fake('private');
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $visit = $this->createVisit($company, $rep);

        // Minimal 1x1 white PNG (89 bytes) — real magic bytes for finfo_buffer
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg==');
        $dataUrl = 'data:image/png;base64,'.base64_encode($png);

        $report = app(VisitReportService::class)->submit($visit, [
            'summary' => 'Signed visit',
        ], $dataUrl);

        $this->assertNotNull($report->signature_path);
        Storage::disk('private')->assertExists($report->signature_path);
    }

    public function test_submit_without_signature_has_null_path(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $visit = $this->createVisit($company, $rep);

        $report = app(VisitReportService::class)->submit($visit, [
            'summary' => 'No signature visit',
        ]);

        $this->assertNull($report->signature_path);
    }
}
