<?php

namespace Tests\Feature\Workflow;

use App\Models\AwardAmendment;
use App\Models\Document;
use App\Models\GmuNotification;
use App\Models\Report;
use App\Services\Sms\NullGateway;
use App\Services\Sms\SmsGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * The last items on the register: amendments and no-cost extensions, donor
 * submission tracking, document retention, and the SMS channel.
 */
class AmendmentsAndRetentionTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    public function test_an_approved_extension_moves_the_award_and_its_calendar(): void
    {
        $coordinator = $this->user('coordinator');
        $award       = $this->award(null, [
            'start_date'          => now()->subMonths(6),
            'end_date'            => now()->addMonths(6),
            'reporting_frequency' => 'quarterly',
        ]);

        app(\App\Services\ComplianceScheduleGenerator::class)->generate($award);
        $reportsBefore = $award->reports()->count();
        $originalEnd   = $award->end_date->copy();

        $this->actingAs($coordinator)->post(route('awards.amendments.store', $award), [
            'type'              => 'no_cost_extension',
            'reason'            => 'Customs clearance delayed the laboratory work by four months.',
            'new_end_date'      => $originalEnd->copy()->addMonths(6)->toDateString(),
            'status'            => 'approved',
            'donor_approved_on' => now()->toDateString(),
        ])->assertRedirect();

        $award->refresh();
        $amendment = AwardAmendment::first();

        // The award now runs longer, and the record shows what changed.
        $this->assertTrue($award->end_date->gt($originalEnd));
        $this->assertSame($originalEnd->toDateString(), $amendment->previous_end_date->toDateString());
        $this->assertGreaterThan(150, $amendment->daysExtended());

        // The reporting calendar follows the contract, so the PI is chased
        // against real deadlines rather than the old period.
        $this->assertGreaterThan($reportsBefore, $award->reports()->count());
    }

    public function test_a_budget_revision_applies_only_when_the_donor_approves(): void
    {
        $coordinator = $this->user('coordinator');
        $award       = $this->award(null, ['total_budget' => 500000, 'currency' => 'USD']);

        $this->actingAs($coordinator)->post(route('awards.amendments.store', $award), [
            'type'       => 'budget_revision',
            'reason'     => 'Additional site added at the donor request.',
            'new_budget' => 650000,
            'status'     => 'submitted',
        ])->assertRedirect();

        $this->assertSame('500000.00', (string) $award->fresh()->total_budget);

        $amendment = AwardAmendment::first();

        $this->actingAs($coordinator)->patch(route('awards.amendments.update', [$award, $amendment]), [
            'status'            => 'approved',
            'donor_approved_on' => now()->toDateString(),
        ])->assertRedirect();

        $award->refresh();
        $this->assertSame('650000.00', (string) $award->total_budget);
        $this->assertSame(150000.0, $amendment->fresh()->budgetDelta());

        // The base-currency figure follows, so portfolio totals stay right.
        $this->assertSame('650000.00', (string) $award->base_total_budget);
    }

    public function test_a_researcher_cannot_amend_an_award(): void
    {
        $pi    = $this->user('pi');
        $award = $this->award($this->proposal($pi));

        $this->actingAs($pi)->post(route('awards.amendments.store', $award), [
            'type'   => 'no_cost_extension',
            'reason' => 'More time needed.',
            'status' => 'approved',
        ])->assertForbidden();

        $this->assertSame(0, AwardAmendment::count());
    }

    public function test_sending_a_report_to_the_donor_is_recorded_separately(): void
    {
        $coordinator = $this->user('coordinator');
        $pi          = $this->user('pi');
        $award       = $this->award($this->proposal($pi));
        $report      = $this->report($award, $pi);

        $payload = [
            'report_type' => 'financial',
            'title'       => $report->title,
            'due_date'    => $report->due_date->toDateString(),
            'status'      => 'approved',
        ];

        $this->actingAs($coordinator)->put(route('reports.update', $report), $payload)->assertRedirect();

        $report->refresh();
        $this->assertNotNull($report->verified_at);
        $this->assertTrue($report->awaitingDonorSubmission());

        $this->actingAs($coordinator)->put(route('reports.update', $report), $payload + [
            'sent_to_donor_on'      => now()->toDateString(),
            'donor_acknowledgement' => 'NIH eRA receipt 55192',
        ])->assertRedirect();

        $report->refresh();
        $this->assertFalse($report->awaitingDonorSubmission());
        $this->assertSame($coordinator->id, $report->sent_by);
        $this->assertSame('NIH eRA receipt 55192', $report->donor_acknowledgement);
    }

    public function test_documents_get_a_retention_date_from_their_category(): void
    {
        Storage::fake('local');

        $coordinator = $this->user('coordinator');

        $this->actingAs($coordinator)->post(route('documents.store'), [
            'title'    => 'Signed donor contract',
            'category' => 'contract',
            'version'  => 1,
            'file'     => UploadedFile::fake()->create('contract.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $document = Document::first();

        // Contracts carry the longest obligation of the categories.
        $this->assertSame(10, $document->retention_years);
        $this->assertSame(now()->addYears(10)->toDateString(), $document->retain_until->toDateString());
        $this->assertFalse($document->isDueForReview());

        $document->update(['retain_until' => now()->subDay()]);
        $this->assertTrue($document->fresh()->isDueForReview());
    }

    public function test_the_sms_channel_stays_inert_until_a_gateway_is_configured(): void
    {
        $gateway = app(SmsGateway::class);

        // Nothing is configured in test or development, so nothing is claimed
        // to have been delivered.
        $this->assertInstanceOf(NullGateway::class, $gateway);
        $this->assertFalse($gateway->isConfigured());
        $this->assertFalse($gateway->send('0754123456', 'Report due in 7 days'));

        // The reminder job runs regardless and still reaches people by the
        // channels that do work.
        $pi     = $this->user('pi', ['phone' => '0754123456']);
        $award  = $this->award($this->proposal($pi));
        $report = $this->report($award, $pi, ['status' => 'pending', 'due_date' => now()->addDays(5)]);

        $this->artisan('gmu:send-deadline-reminders')->assertSuccessful();

        $this->assertTrue(
            GmuNotification::where('user_id', $pi->id)->where('type', 'report_due')->exists()
        );
        $this->assertTrue(Report::find($report->id)->reminder_sent_7);
    }

    public function test_the_health_check_reports_a_misconfigured_mailer(): void
    {
        $this->user('coordinator');
        config(['mail.default' => 'log']);

        // In development a log mailer is a warning: the command still succeeds
        // but says so, which is the point — silence was the original failure.
        $this->artisan('gmu:health')
            ->expectsOutputToContain('MAIL_MAILER is "log"')
            ->assertSuccessful();

        // Under --strict, used by a deployment step, the same state fails.
        $this->artisan('gmu:health --strict')->assertFailed();
    }

    public function test_the_health_check_fails_when_nobody_can_administer_the_system(): void
    {
        // No coordinator at all is not a warning — it means the system cannot
        // be administered.
        $this->artisan('gmu:health')
            ->expectsOutputToContain('No active Coordinator account')
            ->assertFailed();
    }
}
