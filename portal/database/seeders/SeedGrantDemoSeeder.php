<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\User;
use App\Services\SeedGrant\Workflow;
use App\Support\SeedGrant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demonstration data for the seed grant: opens the call and moves a handful
 * of applications through the real workflow to different stages, so every
 * dashboard has something on it. Not run by default:
 *
 *   php artisan db:seed --class=SeedGrantDemoSeeder
 *
 * Demo people use @demo.nimr.or.tz addresses. Never run it on production data.
 */
class SeedGrantDemoSeeder extends Seeder
{
    public function run(Workflow $workflow): void
    {
        $call = SeedCall::firstOrCreate(['code' => '2627'], ['title' => 'Early-Career Researchers’ Implementation Science Seed Grant',
            'fiscal_year' => '2026/27', 'min_amount' => 2_000_000, 'max_amount' => 10_000_000, 'duration_months' => 12]);
        $call->update(['status' => 'open', 'envelope' => 50_000_000,
            'concept_deadline' => now()->addWeeks(3)->toDateString(), 'proposal_deadline' => now()->addWeeks(10)->toDateString()]);

        if ($call->applications()->exists()) {
            $this->command?->warn('The call already has applications; demo data not added.');

            return;
        }

        $gmu  = User::whereHas('role', fn ($q) => $q->where('name', 'coordinator'))->firstOrFail();
        $drcp = User::whereHas('role', fn ($q) => $q->where('name', 'drcp'))->first();

        $reviewers = [
            $this->person('Dr Peter Mboya', 'p.mboya', 'research_scientist', 'Muhimbili Centre', 9),
            $this->person('Dr Sara Kimaro', 's.kimaro', 'research_scientist', 'Tanga Centre', 12),
        ];

        $stories = [
            ['Dr Neema Mushi', 'n.mushi', 'Mwanza Centre', 'Nurse-led follow-up calls to improve retention in HIV care', 'recommended'],
            ['Dr Elia Lyimo', 'e.lyimo', 'Mbeya Medical Research Centre', 'Task-shifting depression screening to antenatal nurses', 'revision'],
            ['Dr Asha Mwakyusa', 'a.mwakyusa', 'Muhimbili Centre', 'Community health worker referral for hypertension in Kinondoni', 'review'],
            ['Dr Baraka Mollel', 'b.mollel', 'Tanga Centre', 'SMS reminders for malaria RDT use in Muheza dispensaries', 'screening'],
            ['Dr Grace Massawe', 'g.massawe', 'Mwanza Centre', 'Fidelity of TB contact tracing in Ilemela', 'draft'],
        ];

        foreach ($stories as [$name, $handle, $centre, $title, $stage]) {
            $pi = $this->person($name, $handle, 'pi', $centre, 3);
            $application = $workflow->start($pi, $call);
            $this->writeConcept($workflow, $application, $title);

            if ($stage === 'draft') {
                continue;
            }
            $workflow->submitConcept($application->fresh());
            if ($stage === 'screening') {
                continue;
            }

            $workflow->screen($application->fresh(), $gmu, ['staff' => true, 'service' => true], true, null);
            foreach ($reviewers as $r) {
                $a = $workflow->assignReviewer($application->fresh(), $r, $gmu);
                $workflow->declareInterest($a, false, null);
            }
            $workflow->startReview($application->fresh());
            $this->reviewAll($workflow, $application, $stage === 'review' ? 1 : 2, ['accept', 'revise']);
            if ($stage === 'review') {
                continue;
            }

            if ($stage === 'revision') {
                $workflow->decide($application->fresh(), $gmu, 'revise', 'Please describe the referral pathway for women who screen positive.');
                continue;
            }

            // Concept accepted, full proposal written, scored and recommended.
            $workflow->decide($application->fresh(), $gmu, 'advance', null);
            $this->writeProposal($workflow, $application->fresh());
            $workflow->submitProposal($application->fresh());
            foreach ($reviewers as $r) {
                $a = $workflow->assignReviewer($application->fresh(), $r, $gmu);
                $workflow->declareInterest($a, false, null);
            }
            $workflow->startReview($application->fresh());
            $this->reviewAll($workflow, $application, 2, ['accept', 'accept'], true);
            $workflow->decide($application->fresh(), $gmu, 'advance', null);
            $workflow->approve($application->fresh(), $gmu, 'gmu', 'approved', null);
        }

        $this->command?->info('Seed grant demo data added. Demo sign-ins use @demo.nimr.or.tz with the password "demo-password".');
    }

    private function person(string $name, string $handle, string $role, string $centre, int $years): User
    {
        $user = User::firstOrNew(['email' => "{$handle}@demo.nimr.or.tz"]);
        $user->fill([
            'name' => $name, 'password' => 'demo-password', 'is_active' => true,
            'role_id' => Role::where('name', $role)->value('id'),
            'research_center' => $centre, 'designation' => $role === 'pi' ? 'Research Scientist II' : 'Senior Research Scientist',
            'employment_type' => 'permanent', 'first_appointment_date' => now()->subYears($years)->subMonths(4),
        ]);
        $user->save();

        return $user;
    }

    private function text(int $words, string $topic): string
    {
        $sentences = [
            "This study examines how {$topic} can be delivered reliably in routine primary care.",
            'We will work with facility in-charges and district teams from the start.',
            'Implementation outcomes are adoption, fidelity and reach, measured monthly.',
            'Findings will be shared with the council health management team and the programme.',
        ];
        $out = [];
        while (str_word_count(implode(' ', $out)) < $words) {
            $out[] = $sentences[count($out) % count($sentences)];
        }

        return implode(' ', $out);
    }

    private function writeConcept(Workflow $workflow, SeedApplication $application, string $title): void
    {
        $topic = Str::lower(Str::before($title, ' to ')) ?: 'the intervention';
        $concept = [];
        foreach (SeedGrant::CONCEPT_SECTIONS as $key => [, , $limit]) {
            $concept[$key] = $this->text((int) ($limit * 0.6), $topic);
        }
        $application->fill([
            'title' => $title, 'concept' => $concept, 'declared_no_prior_large_grant' => true,
            'mentor_name' => 'Dr Joseph Kweka', 'mentor_designation' => 'Chief Research Scientist',
            'mentor_centre' => $application->pi->research_center, 'mentor_suitability' => 'Has led implementation studies in the same facilities and supervises two early-career scientists.',
        ])->save();
        $workflow->saveBudget($application, 'concept', [
            ['item' => 'Research assistant allowances', 'amount' => 3_600_000],
            ['item' => 'Travel to facilities for data collection', 'amount' => 1_850_000],
            ['item' => 'Stakeholder meetings', 'amount' => 900_000],
            ['item' => 'Ethics review fee', 'amount' => 300_000],
            ['item' => 'Dissemination workshop', 'amount' => 600_000],
        ]);
    }

    private function writeProposal(Workflow $workflow, SeedApplication $application): void
    {
        $proposal = [];
        foreach (SeedGrant::PROPOSAL_SECTIONS as $key => [, , $limit]) {
            $proposal[$key] = $this->text((int) ($limit * 0.5), 'nurse-led follow-up');
        }
        $application->forceFill(['proposal' => $proposal])->save();
        $workflow->saveBudget($application, 'proposal', [
            ['category' => 'personnel', 'item' => 'Research assistants (2)', 'q1' => 900_000, 'q2' => 900_000, 'q3' => 900_000, 'q4' => 900_000],
            ['category' => 'equipment', 'item' => 'Mobile phones for follow-up calls', 'q1' => 950_000],
            ['category' => 'research', 'item' => 'Travel and data collection', 'q1' => 1_000_000, 'q2' => 800_000, 'q3' => 800_000, 'q4' => 530_000],
            ['category' => 'indirect', 'item' => 'Institutional overheads', 'q1' => 200_000, 'q2' => 200_000, 'q3' => 200_000, 'q4' => 170_000],
        ]);
        foreach (['mentor_letter' => 'Mentor commitment letter.pdf', 'cv' => 'Applicant CV.pdf'] as $kind => $file) {
            $path = 'seed-grant/' . $application->reference . '/' . Str::random(12) . '.pdf';
            Storage::disk('local')->put($path, "%PDF-1.4\n% demo placeholder\n");
            $application->documents()->create(['kind' => $kind, 'file_path' => $path, 'file_name' => $file, 'file_size' => 32]);
        }
    }

    private function reviewAll(Workflow $workflow, SeedApplication $application, int $howMany, array $recommendations, bool $scored = false): void
    {
        $application->refresh()->load('assignments.application');
        $comments = [
            'The implementation question is clear and the facilities are committed. The outcomes are well chosen.',
            'Name an implementation framework and narrow the outcomes to adoption and fidelity for a 12-month study.',
        ];
        foreach ($application->activeAssignments()->take($howMany)->values() as $i => $assignment) {
            $workflow->saveReview($assignment->fresh(), [
                'recommendation' => $recommendations[$i] ?? 'accept',
                'comments'       => $comments[$i] ?? $comments[0],
                'scores'         => $scored ? ['relevance' => 17, 'science' => 21.5 - $i, 'feasibility' => 17, 'impact' => 13, 'development' => 9, 'innovation' => 7] : [],
            ], true);
        }
    }
}
