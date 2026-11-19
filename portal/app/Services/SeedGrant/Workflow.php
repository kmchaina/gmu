<?php

namespace App\Services\SeedGrant;

use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedApproval;
use App\Models\Seed\SeedCall;
use App\Models\Seed\SeedReview;
use App\Models\Seed\SeedReviewerAssignment;
use App\Models\Seed\SeedReviewRound;
use App\Models\User;
use App\Services\Notifier;
use App\Support\SeedGrant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every change of state in a seed grant application goes through here: the
 * checks the call document requires, the status change, and who is told.
 * Controllers authorise and collect input; this decides.
 */
class Workflow
{
    /** Reviewers each round needs before GMU can send it out. */
    public const MIN_REVIEWERS = 2;

    public const REVIEW_DAYS = 14;
    public const RESPONSE_DAYS = 14;

    public function __construct(private Notifier $notifier) {}

    // ── Applicant ───────────────────────────────────────────────────────────

    public function start(User $pi, SeedCall $call): SeedApplication
    {
        if (! $call->acceptingConceptNotes()) {
            $this->fail('call', 'This call is not accepting concept notes.');
        }

        $existing = SeedApplication::where('seed_call_id', $call->id)->where('pi_id', $pi->id)->first();
        if ($existing) {
            return $existing;
        }

        $this->assertEligible($pi);

        return DB::transaction(function () use ($pi, $call) {
            $next = SeedApplication::where('seed_call_id', $call->id)->lockForUpdate()->count() + 1;

            $application = new SeedApplication();
            $application->seed_call_id = $call->id;
            $application->pi_id = $pi->id;
            $application->reference = sprintf('SG-%s-%03d', $call->code, $next);
            $application->status = SeedGrant::DRAFT;
            $application->save();

            return $application;
        });
    }

    /** Replace the budget lines for a stage with the rows given, dropping empty ones. */
    public function saveBudget(SeedApplication $application, string $stage, array $rows): void
    {
        DB::transaction(function () use ($application, $stage, $rows) {
            $application->budgetItems()->where('stage', $stage)->delete();

            $position = 0;
            foreach ($rows as $row) {
                $item = trim((string) ($row['item'] ?? ''));
                $amounts = $stage === 'concept'
                    ? ['q1' => $this->amount($row['amount'] ?? 0), 'q2' => 0, 'q3' => 0, 'q4' => 0]
                    : ['q1' => $this->amount($row['q1'] ?? 0), 'q2' => $this->amount($row['q2'] ?? 0),
                       'q3' => $this->amount($row['q3'] ?? 0), 'q4' => $this->amount($row['q4'] ?? 0)];

                if ($item === '' && array_sum($amounts) === 0) {
                    continue;
                }

                $application->budgetItems()->create($amounts + [
                    'stage'    => $stage,
                    'category' => $stage === 'proposal' ? ($row['category'] ?? null) : null,
                    'item'     => mb_substr($item, 0, 255),
                    'position' => $position++,
                ]);
            }
        });

        $application->unsetRelation('budgetItems');
    }

    public function submitConcept(SeedApplication $application): void
    {
        $this->assertStatus($application, [SeedGrant::DRAFT]);
        $this->assertEligible($application->pi);

        if (! $application->call->acceptingConceptNotes()) {
            $this->fail('call', 'The concept note deadline has passed.');
        }

        $this->assertComplete($application, 'concept');

        $application->status = SeedGrant::SUBMITTED;
        $application->concept_submitted_at = now();
        $application->save();

        $this->notifier->send($this->gmu(), 'seed_submitted', 'Concept note to screen: ' . $application->reference,
            $application->pi->name . ' submitted “' . $application->title . '”. It is waiting for GMU screening.', $application);
    }

    public function submitProposal(SeedApplication $application): void
    {
        $this->assertStatus($application, [SeedGrant::SHORTLISTED]);

        if (! $application->call->acceptingProposals()) {
            $this->fail('call', 'The full proposal deadline has passed.');
        }

        $this->assertComplete($application, 'proposal');

        $application->status = SeedGrant::PROPOSAL_SUBMITTED;
        $application->proposal_submitted_at = now();
        $application->save();

        $this->notifier->send($this->gmu(), 'seed_submitted', 'Full proposal received: ' . $application->reference,
            $application->pi->name . ' submitted the full proposal. Assign reviewers to start the scored review.', $application);
    }

    /** The applicant answers a "revise" decision; the same reviewers look again. */
    public function resubmit(SeedApplication $application, string $response): SeedReviewRound
    {
        $round = $application->roundAwaitingResponse();
        if (! $round) {
            $this->fail('response', 'There is no revision request to respond to.');
        }
        if (trim($response) === '') {
            $this->fail('response', 'Tell the reviewers what you changed.');
        }

        $stage = $round->stage;
        $this->assertComplete($application, $stage);

        return DB::transaction(function () use ($application, $round, $response, $stage) {
            $round->update(['response' => trim($response), 'responded_at' => now()]);

            $next = $application->rounds()->create([
                'stage'  => $stage,
                'number' => $round->number + 1,
                'due_at' => now()->addDays(self::REVIEW_DAYS)->toDateString(),
            ]);

            $application->status = $stage === 'concept' ? SeedGrant::CONCEPT_REVIEW : SeedGrant::PROPOSAL_REVIEW;
            $application->save();
            $application->unsetRelation('rounds');

            $this->notifier->send($this->reviewers($application, $stage), 'seed_review', 'Revised ' . $stage . ' to review: ' . $application->reference,
                'The applicant has responded to round ' . $round->number . '. Please review round ' . $next->number . ' by ' . $next->due_at->format('j M Y') . '.', $application);
            $this->notifier->send($this->gmu(), 'seed_resubmitted', 'Revision received: ' . $application->reference,
                'Round ' . $next->number . ' is open with the same reviewers.', $application);

            return $next;
        });
    }

    // ── GMU ─────────────────────────────────────────────────────────────────

    /**
     * Administrative screening: eligibility, completeness, compliance.
     *
     * @param  array<string,bool>  $checks
     */
    public function screen(SeedApplication $application, User $by, array $checks, bool $eligible, ?string $note): void
    {
        $this->assertStatus($application, [SeedGrant::SUBMITTED]);

        if (! $eligible && blank($note)) {
            $this->fail('screening_note', 'Give the applicant the reason it is not eligible.');
        }

        $application->screening_checks = $checks;
        $application->screening_note = $note;
        $application->screened_by = $by->id;
        $application->screened_at = now();
        $application->status = $eligible ? SeedGrant::SCREENED : SeedGrant::INELIGIBLE;
        $application->save();

        $this->notifier->send($application->pi, 'seed_screened',
            $eligible ? 'Your concept note passed screening' : 'Your concept note is not eligible',
            $eligible ? 'GMU found ' . $application->reference . ' eligible and complete. It now goes to reviewers.'
                      : 'GMU found ' . $application->reference . ' not eligible: ' . $note,
            $application);
    }

    public function assignReviewer(SeedApplication $application, User $reviewer, User $by): SeedReviewerAssignment
    {
        $stage = $application->stage();
        $this->assertStatus($application, $stage === 'concept'
            ? [SeedGrant::SCREENED, SeedGrant::CONCEPT_REVIEW, SeedGrant::CONCEPT_REVISION]
            : [SeedGrant::PROPOSAL_SUBMITTED, SeedGrant::PROPOSAL_REVIEW, SeedGrant::PROPOSAL_REVISION]);

        if ($reviewer->id === $application->pi_id) {
            $this->fail('reviewer_id', 'An applicant cannot review their own application.');
        }
        if (! $reviewer->is_active) {
            $this->fail('reviewer_id', 'That account is deactivated.');
        }

        $existing = $application->assignments()->where('stage', $stage)->where('reviewer_id', $reviewer->id)->first();
        if ($existing && $existing->removed_at === null) {
            $this->fail('reviewer_id', $reviewer->name . ' is already a reviewer on this application.');
        }

        if ($existing) {
            $existing->update(['removed_at' => null, 'coi_status' => 'pending', 'coi_note' => null, 'coi_declared_at' => null, 'assigned_by' => $by->id]);
            $assignment = $existing;
        } else {
            $used = $application->assignments()->where('stage', $stage)->count();
            $assignment = $application->assignments()->create([
                'stage'       => $stage,
                'reviewer_id' => $reviewer->id,
                'label'       => chr(ord('A') + min($used, 25)),
                'coi_status'  => 'pending',
                'assigned_by' => $by->id,
            ]);
        }
        $application->unsetRelation('assignments');

        $this->notifier->send($reviewer, 'seed_review', 'Review request: ' . $application->reference,
            'GMU has asked you to review a seed grant ' . $stage . '. First declare whether you have any conflict of interest.', $application);

        return $assignment;
    }

    public function removeReviewer(SeedReviewerAssignment $assignment): void
    {
        $assignment->update(['removed_at' => now()]);
    }

    /** GMU sends the application to its reviewers: round 1 of the current stage. */
    public function startReview(SeedApplication $application, ?string $dueAt = null): SeedReviewRound
    {
        $stage = $application->stage();
        $this->assertStatus($application, [SeedGrant::SCREENED, SeedGrant::PROPOSAL_SUBMITTED]);

        if ($application->activeAssignments($stage)->count() < self::MIN_REVIEWERS) {
            $this->fail('reviewers', 'Assign at least ' . self::MIN_REVIEWERS . ' reviewers first.');
        }

        $due = $dueAt ? Carbon::parse($dueAt) : now()->addDays(self::REVIEW_DAYS);
        if ($due->isPast()) {
            $this->fail('due_at', 'The review due date must be in the future.');
        }

        return DB::transaction(function () use ($application, $stage, $due) {
            $round = $application->rounds()->create(['stage' => $stage, 'number' => 1, 'due_at' => $due->toDateString()]);

            $application->status = $stage === 'concept' ? SeedGrant::CONCEPT_REVIEW : SeedGrant::PROPOSAL_REVIEW;
            $application->save();
            $application->unsetRelation('rounds');

            $this->notifier->send($this->reviewers($application, $stage), 'seed_review', 'Review open: ' . $application->reference,
                'Round 1 is open. Please submit your review by ' . $due->format('j M Y') . '.', $application);

            return $round;
        });
    }

    /**
     * GMU decides a round: revise (back to the applicant), advance (shortlist
     * or recommend) or decline.
     */
    public function decide(SeedApplication $application, User $by, string $decision, ?string $note, ?string $responseDue = null): void
    {
        $round = $application->openRound();
        if (! $round) {
            $this->fail('decision', 'There is no open review round to decide.');
        }
        if (! array_key_exists($decision, SeedReviewRound::DECISIONS)) {
            $this->fail('decision', 'Choose revise, advance or decline.');
        }
        if ($round->reviews()->whereNotNull('submitted_at')->doesntExist()) {
            $this->fail('decision', 'Wait for at least one submitted review.');
        }

        $stage = $round->stage;
        if ($stage === 'proposal' && $decision === 'advance' && ! $application->meetsThreshold()) {
            $this->fail('decision', sprintf('Below the funding threshold: a proposal needs at least %d overall and %s for scientific quality.',
                SeedGrant::PASS_TOTAL, rtrim(rtrim(number_format(SeedGrant::minScience(), 1), '0'), '.')));
        }
        if ($decision === 'revise' && blank($note)) {
            $this->fail('decision_note', 'Tell the applicant what to revise.');
        }

        $responseBy = $decision === 'revise'
            ? ($responseDue ? Carbon::parse($responseDue) : now()->addDays(self::RESPONSE_DAYS))
            : null;

        $round->update([
            'decision'        => $decision,
            'decision_note'   => $note,
            'response_due_at' => $responseBy?->toDateString(),
            'decided_by'      => $by->id,
            'decided_at'      => now(),
        ]);

        $application->status = match ([$stage, $decision]) {
            ['concept', 'revise']   => SeedGrant::CONCEPT_REVISION,
            ['concept', 'advance']  => SeedGrant::SHORTLISTED,
            ['concept', 'decline']  => SeedGrant::CONCEPT_DECLINED,
            ['proposal', 'revise']  => SeedGrant::PROPOSAL_REVISION,
            ['proposal', 'advance'] => SeedGrant::RECOMMENDED,
            ['proposal', 'decline'] => SeedGrant::NOT_RECOMMENDED,
        };
        $application->save();

        [$title, $message] = match ($application->status) {
            SeedGrant::CONCEPT_REVISION, SeedGrant::PROPOSAL_REVISION => [
                'Reviewers asked you to revise ' . $application->reference,
                'Read the reviewers’ comments and respond by ' . $responseBy->format('j M Y') . '.'],
            SeedGrant::SHORTLISTED => [
                'You are shortlisted: ' . $application->reference,
                'You are invited to submit a full proposal' . ($application->call->proposal_deadline ? ' by ' . $application->call->proposal_deadline->format('j M Y') : '') . '. Shortlisting does not guarantee an award.'],
            SeedGrant::RECOMMENDED => [
                'Recommended for funding: ' . $application->reference,
                'The review panel recommends your proposal. It now goes for institutional approval.'],
            default => [
                'Decision on ' . $application->reference,
                'Your ' . $stage . ' was not taken forward.' . ($note ? ' ' . $note : '')],
        };
        $this->notifier->send($application->pi, 'seed_decision', $title, $message, $application);
    }

    // ── Reviewers ───────────────────────────────────────────────────────────

    public function declareInterest(SeedReviewerAssignment $assignment, bool $conflict, ?string $note): void
    {
        if ($assignment->coi_status !== 'pending' || $assignment->removed_at) {
            $this->fail('coi', 'You have already made a declaration for this review.');
        }
        if ($conflict && blank($note)) {
            $this->fail('coi_note', 'Describe the conflict so GMU can reassign the review.');
        }

        $assignment->update(['coi_status' => $conflict ? 'conflict' : 'none', 'coi_note' => $note, 'coi_declared_at' => now()]);

        if ($conflict) {
            $this->notifier->send($this->gmu(), 'seed_conflict', 'Conflict declared on ' . $assignment->application->reference,
                $assignment->reviewer->name . ' declared a conflict of interest and will not review it. Assign another reviewer.', $assignment->application);
        }
    }

    /**
     * Save or submit a review in the open round.
     *
     * @param  array{recommendation?:?string, comments?:?string, confidential_note?:?string, scores?:array}  $data
     */
    public function saveReview(SeedReviewerAssignment $assignment, array $data, bool $submit): SeedReview
    {
        if (! $assignment->canReview()) {
            $this->fail('review', 'Declare your interests before reviewing.');
        }

        $application = $assignment->application;
        $round = $application->openRound($assignment->stage);
        if (! $round) {
            $this->fail('review', 'This review round is closed.');
        }

        $review = SeedReview::firstOrNew(['seed_review_round_id' => $round->id, 'seed_reviewer_assignment_id' => $assignment->id]);
        if ($review->isSubmitted()) {
            $this->fail('review', 'You have already submitted this review.');
        }

        $review->fill([
            'recommendation'    => $data['recommendation'] ?? null,
            'comments'          => $data['comments'] ?? null,
            'confidential_note' => $data['confidential_note'] ?? null,
        ]);

        if ($assignment->stage === 'proposal') {
            $scores = [];
            foreach (SeedGrant::CRITERIA as $key => [, , $max]) {
                $value = $data['scores'][$key] ?? null;
                $scores[$key] = $value === null || $value === '' ? null : max(0, min($max, round((float) $value * 2) / 2));
            }
            $review->scores = $scores;
            $review->total = in_array(null, $scores, true) ? null : array_sum($scores);
        }

        if ($submit) {
            $missing = [];
            if (! array_key_exists($review->recommendation, SeedReview::RECOMMENDATIONS)) {
                $missing['recommendation'] = 'Choose a recommendation.';
            }
            if (blank($review->comments)) {
                $missing['comments'] = 'Write comments for the applicant.';
            }
            if ($assignment->stage === 'proposal' && $review->total === null) {
                $missing['scores'] = 'Score every criterion.';
            }
            if ($missing) {
                throw ValidationException::withMessages($missing);
            }
            $review->submitted_at = now();
        }

        $review->round()->associate($round);
        $review->save();

        if ($submit && $this->roundComplete($round)) {
            $this->notifier->send($this->gmu(), 'seed_reviews_in', 'Reviews complete: ' . $application->reference,
                'All reviewers have submitted round ' . $round->number . '. It is ready for your decision.', $application);
        }

        return $review;
    }

    public function roundComplete(SeedReviewRound $round): bool
    {
        $reviewers = $round->application->assignments()->where('stage', $round->stage)->active()->pluck('id');
        $submitted = $round->reviews()->whereNotNull('submitted_at')->pluck('seed_reviewer_assignment_id');

        return $reviewers->isNotEmpty() && $reviewers->diff($submitted)->isEmpty();
    }

    // ── Approval: GMU, then DRCP, then the Director General ─────────────────

    public function approve(SeedApplication $application, User $by, string $level, string $decision, ?string $note): void
    {
        $expected = ['gmu' => SeedGrant::RECOMMENDED, 'drcp' => SeedGrant::WITH_DRCP, 'dg' => SeedGrant::WITH_DG][$level] ?? null;
        $this->assertStatus($application, [$expected]);

        $allowed = $level === 'dg' ? ['approved', 'conditions', 'returned'] : ['approved', 'returned'];
        if (! in_array($decision, $allowed, true)) {
            $this->fail('decision', 'Choose a decision.');
        }
        if ($level === 'gmu' && $decision === 'returned') {
            $this->fail('decision', 'GMU sends recommended proposals on; to change the panel’s recommendation, reopen the review.');
        }
        if (in_array($decision, ['returned', 'conditions'], true) && blank($note)) {
            $this->fail('note', $decision === 'returned' ? 'Give the reason for returning it.' : 'State the conditions.');
        }

        $application->approvals()->create(['level' => $level, 'decision' => $decision, 'note' => $note, 'decided_by' => $by->id]);

        if ($decision === 'returned') {
            $application->status = SeedGrant::RECOMMENDED;
            $application->save();
            $this->notifier->send($this->gmu(), 'seed_returned', SeedApproval::LEVELS[$level] . ' returned ' . $application->reference, (string) $note, $application);

            return;
        }

        $application->status = match ($level) {
            'gmu'  => SeedGrant::WITH_DRCP,
            'drcp' => SeedGrant::WITH_DG,
            'dg'   => SeedGrant::APPROVED,
        };

        if ($level === 'dg') {
            $application->approved_amount = $application->budgetTotal('proposal');
            $application->approval_conditions = $decision === 'conditions' ? $note : null;
        }
        $application->save();

        match ($level) {
            'gmu'  => $this->notifier->send($this->usersWithRole('drcp'), 'seed_approval', 'For DRCP clearance: ' . $application->reference,
                        'GMU has sent a recommended seed grant for your clearance.', $application),
            'drcp' => $this->notifier->send($this->usersWithRole('dg'), 'seed_approval', 'For your approval: ' . $application->reference,
                        'Cleared by DRCP. Awaiting the Director General’s decision.', $application),
            'dg'   => $this->notifier->send(collect([$application->pi])->merge($this->gmu()), 'seed_approved', 'Approved: ' . $application->reference,
                        'The Director General approved the seed grant. GMU will issue the grant agreement.', $application),
        };
    }

    // ── Grant agreement ─────────────────────────────────────────────────────

    public function issueAgreement(SeedApplication $application): void
    {
        $this->assertStatus($application, [SeedGrant::APPROVED]);

        $application->status = SeedGrant::AGREEMENT_ISSUED;
        $application->agreement_issued_at = now();
        $application->save();

        $this->notifier->send($application->pi, 'seed_agreement', 'Your grant agreement is ready',
            'Read and accept the agreement for ' . $application->reference . ' online.', $application);
    }

    public function acceptAgreement(SeedApplication $application): void
    {
        $this->assertStatus($application, [SeedGrant::AGREEMENT_ISSUED]);

        $application->status = SeedGrant::AGREEMENT_ACCEPTED;
        $application->agreement_accepted_at = now();
        $application->save();

        $this->notifier->send($this->gmu(), 'seed_agreement', 'Agreement accepted: ' . $application->reference,
            $application->pi->name . ' accepted the grant agreement. Collect the signatures and upload the signed copy.', $application);
    }

    public function recordSignedAgreement(SeedApplication $application, string $path, string $name): void
    {
        $this->assertStatus($application, [SeedGrant::AGREEMENT_ACCEPTED, SeedGrant::AWARDED]);

        $application->signed_agreement_path = $path;
        $application->signed_agreement_name = $name;
        $application->signed_agreement_uploaded_at = now();
        $application->status = SeedGrant::AWARDED;
        $application->save();

        $this->notifier->send($application->pi, 'seed_agreement', 'Signed agreement on file: ' . $application->reference,
            'The fully signed grant agreement is now in the portal.', $application);
    }

    // ── Checks ──────────────────────────────────────────────────────────────

    /** Rules on the lead applicant from section 4 of the call. */
    public function eligibilityProblems(User $pi): array
    {
        $problems = [];
        if (! $pi->employment_type) {
            $problems[] = 'Your employment type is not recorded.';
        }
        if (! $pi->first_appointment_date) {
            $problems[] = 'Your date of first appointment at NIMR is not recorded.';
        } elseif (! $pi->withinSeedGrantServiceLimit()) {
            $problems[] = 'Lead applicants must have served at NIMR for under ' . User::MAX_SERVICE_YEARS_FOR_SEED_GRANT . ' years.';
        }

        return $problems;
    }

    /** What still stops a stage from being submitted, keyed by field. */
    public function completenessProblems(SeedApplication $application, string $stage): array
    {
        $application->loadMissing(['budgetItems', 'documents', 'call']);
        $call = $application->call;
        $problems = [];

        if ($stage === 'concept') {
            if (blank($application->title)) {
                $problems['title'] = 'Give the project a title.';
            }
            foreach (['mentor_name' => 'name', 'mentor_designation' => 'designation', 'mentor_centre' => 'centre or section', 'mentor_suitability' => 'suitability'] as $field => $label) {
                if (blank($application->{$field})) {
                    $problems[$field] = 'Give your mentor’s ' . $label . '.';
                }
            }
            if (! $application->declared_no_prior_large_grant) {
                $problems['declared_no_prior_large_grant'] = 'Confirm you have not held a research grant above USD 10,000 as PI.';
            }
        }

        foreach (SeedGrant::sections($stage) as $key => [$heading, , $limit]) {
            $words = SeedGrant::wordCount($application->sectionText($stage, $key));
            if ($words === 0) {
                $problems["{$stage}.{$key}"] = $heading . ': this section is empty.';
            } elseif ($words > $limit) {
                $problems["{$stage}.{$key}"] = $heading . ": {$words} words, over the {$limit}-word limit.";
            }
        }

        $total = $application->budgetTotal($stage);
        if ($application->budgetLines($stage)->isEmpty()) {
            $problems['budget'] = 'Add your budget.';
        } elseif ($total < $call->min_amount || $total > $call->max_amount) {
            $problems['budget'] = sprintf('The budget must be between %s and %s; it is %s.',
                SeedGrant::tzs($call->min_amount), SeedGrant::tzs($call->max_amount), SeedGrant::tzs($total));
        }

        if ($stage === 'proposal') {
            if ($application->budgetLines('proposal')->contains(fn ($l) => ! array_key_exists((string) $l->category, SeedGrant::BUDGET_CATEGORIES))) {
                $problems['budget_category'] = 'Put every budget line under personnel, equipment, research or indirect costs.';
            }
            $equipment = $application->budgetByCategory()['equipment'];
            if ($total > 0 && $equipment > $total * $call->equipment_cap_percent / 100) {
                $problems['budget_equipment'] = sprintf('Equipment is %.1f%% of the budget; the limit is %d%%.', $equipment / $total * 100, $call->equipment_cap_percent);
            }
            foreach (SeedGrant::DOCUMENT_KINDS as $kind => [$label, $required]) {
                if ($required && $application->documents->where('kind', $kind)->isEmpty()) {
                    $problems["document_{$kind}"] = 'Upload the ' . mb_strtolower($label) . '.';
                }
            }
        }

        return $problems;
    }

    private function assertEligible(User $pi): void
    {
        if ($problems = $this->eligibilityProblems($pi)) {
            throw ValidationException::withMessages(['eligibility' => $problems]);
        }
    }

    private function assertComplete(SeedApplication $application, string $stage): void
    {
        if ($problems = $this->completenessProblems($application, $stage)) {
            throw ValidationException::withMessages($problems);
        }
    }

    private function assertStatus(SeedApplication $application, array $allowed): void
    {
        if (! in_array($application->status, $allowed, true)) {
            $this->fail('status', 'This cannot be done while the application is “' . $application->statusLabel() . '”.');
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    private function amount(mixed $value): int
    {
        return max(0, (int) preg_replace('/[^0-9]/', '', (string) $value));
    }

    // ── Who is told ─────────────────────────────────────────────────────────

    /** GMU staff who run the call. */
    public function gmu()
    {
        return $this->usersWithRole(['coordinator', 'planning_officer']);
    }

    private function usersWithRole(string|array $roles)
    {
        return User::where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('name', (array) $roles))
            ->get();
    }

    private function reviewers(SeedApplication $application, string $stage)
    {
        return $application->assignments()->where('stage', $stage)->active()->with('reviewer')->get()->pluck('reviewer');
    }
}
