<?php

namespace App\Support;

/**
 * The fixed rules of the seed grant call, in one place: the sections each
 * stage asks for, the scoring rubric, the thresholds and the status labels.
 * Taken from the FY 2026/27 call document.
 */
final class SeedGrant
{
    // ── Statuses ────────────────────────────────────────────────────────────

    public const DRAFT              = 'draft';
    public const SUBMITTED          = 'submitted';          // concept note awaiting GMU screening
    public const SCREENED           = 'screened';           // eligible, awaiting reviewers
    public const INELIGIBLE         = 'ineligible';
    public const CONCEPT_REVIEW     = 'concept_review';
    public const CONCEPT_REVISION   = 'concept_revision';
    public const CONCEPT_DECLINED   = 'concept_declined';
    public const SHORTLISTED        = 'shortlisted';        // writing the full proposal
    public const PROPOSAL_SUBMITTED = 'proposal_submitted'; // awaiting reviewers
    public const PROPOSAL_REVIEW    = 'proposal_review';
    public const PROPOSAL_REVISION  = 'proposal_revision';
    public const NOT_RECOMMENDED    = 'not_recommended';
    public const RECOMMENDED        = 'recommended';        // panel recommends; awaiting GMU sign-off
    public const WITH_DRCP          = 'with_drcp';
    public const WITH_DG            = 'with_dg';
    public const APPROVED           = 'approved';           // awaiting the agreement
    public const AGREEMENT_ISSUED   = 'agreement_issued';
    public const AGREEMENT_ACCEPTED = 'agreement_accepted';
    public const AWARDED            = 'awarded';            // signed copy on file

    public const STATUS_LABELS = [
        self::DRAFT              => 'Concept note draft',
        self::SUBMITTED          => 'Awaiting screening',
        self::SCREENED           => 'Awaiting reviewers',
        self::INELIGIBLE         => 'Not eligible',
        self::CONCEPT_REVIEW     => 'Concept review',
        self::CONCEPT_REVISION   => 'Revision requested',
        self::CONCEPT_DECLINED   => 'Not shortlisted',
        self::SHORTLISTED        => 'Shortlisted',
        self::PROPOSAL_SUBMITTED => 'Proposal awaiting reviewers',
        self::PROPOSAL_REVIEW    => 'Scored review',
        self::PROPOSAL_REVISION  => 'Revision requested',
        self::NOT_RECOMMENDED    => 'Not recommended',
        self::RECOMMENDED        => 'Recommended',
        self::WITH_DRCP          => 'With DRCP',
        self::WITH_DG            => 'With the DG',
        self::APPROVED           => 'Approved',
        self::AGREEMENT_ISSUED   => 'Agreement issued',
        self::AGREEMENT_ACCEPTED => 'Agreement accepted',
        self::AWARDED            => 'Awarded',
    ];

    /** Pill colour per status: blue done/positive, amber waiting on someone, red stopped, grey in progress. */
    public const STATUS_TONES = [
        self::DRAFT => '', self::SUBMITTED => '', self::SCREENED => 'info',
        self::INELIGIBLE => 'danger', self::CONCEPT_REVIEW => 'navy', self::CONCEPT_REVISION => 'warn',
        self::CONCEPT_DECLINED => 'danger', self::SHORTLISTED => 'ok', self::PROPOSAL_SUBMITTED => 'info',
        self::PROPOSAL_REVIEW => 'navy', self::PROPOSAL_REVISION => 'warn', self::NOT_RECOMMENDED => 'danger',
        self::RECOMMENDED => 'ok', self::WITH_DRCP => 'warn', self::WITH_DG => 'warn', self::APPROVED => 'ok',
        self::AGREEMENT_ISSUED => 'warn', self::AGREEMENT_ACCEPTED => 'ok', self::AWARDED => 'ok',
    ];

    /** Statuses in which the applicant may edit the concept note or proposal. */
    public const APPLICANT_EDITABLE = [self::DRAFT, self::CONCEPT_REVISION, self::SHORTLISTED, self::PROPOSAL_REVISION];

    public const CONCEPT_STATUSES = [self::DRAFT, self::SUBMITTED, self::SCREENED, self::INELIGIBLE,
        self::CONCEPT_REVIEW, self::CONCEPT_REVISION, self::CONCEPT_DECLINED];

    public const CLOSED_STATUSES = [self::INELIGIBLE, self::CONCEPT_DECLINED, self::NOT_RECOMMENDED];

    /** The eight steps shown to the applicant, and which statuses fall in each. */
    public const TRACK = [
        ['Concept note', 'Submit online', [self::DRAFT]],
        ['GMU screening', 'Eligibility check', [self::SUBMITTED, self::INELIGIBLE]],
        ['Concept review', 'Accept, revise or reject', [self::SCREENED, self::CONCEPT_REVIEW, self::CONCEPT_REVISION, self::CONCEPT_DECLINED]],
        ['Full proposal', 'If shortlisted', [self::SHORTLISTED]],
        ['Scored review', 'Six criteria', [self::PROPOSAL_SUBMITTED, self::PROPOSAL_REVIEW, self::PROPOSAL_REVISION, self::NOT_RECOMMENDED]],
        ['Approval', 'GMU · DRCP · DG', [self::RECOMMENDED, self::WITH_DRCP, self::WITH_DG]],
        ['Agreement', 'Accept online', [self::APPROVED, self::AGREEMENT_ISSUED, self::AGREEMENT_ACCEPTED]],
        ['Awarded', 'Signed copy on file', [self::AWARDED]],
    ];

    // ── What each stage asks for ────────────────────────────────────────────

    /** Concept note sections: key => [heading, guidance, word limit]. About five pages in all. */
    public const CONCEPT_SECTIONS = [
        'problem'    => ['Problem and implementation gap', 'The implementation problem, brief evidence of the gap, the setting and the intended beneficiaries.', 450],
        'aims'       => ['Aim, objectives and research questions', 'The overall aim, specific objectives and the focused implementation research question(s).', 300],
        'methods'    => ['Framework, design and methods', 'Implementation science framework or rationale, study design, setting, participants or data sources, and key methods.', 550],
        'strategies' => ['Implementation strategies and outcomes', 'Strategies you will test and the outcomes you will measure (acceptability, adoption, appropriateness, feasibility, fidelity, reach, cost or sustainability, as relevant).', 350],
        'outputs'    => ['Outputs and use of findings', 'Expected outputs, who will use the findings and a practical dissemination and use plan.', 250],
        'workplan'   => ['Workplan and milestones', 'Key activities and milestones across the 12 months.', 250],
    ];

    /** Full proposal sections. About ten pages in all, excluding references, budget and letters. */
    public const PROPOSAL_SECTIONS = [
        'summary'       => ['Executive summary', 'A clear statement of the implementation problem and what the study will do.', 300],
        'background'    => ['Background, evidence gap and significance', 'Rationale and significance to Tanzanian health priorities.', 700],
        'objectives'    => ['Objectives, research questions or hypotheses', '', 300],
        'framework'     => ['Implementation science framework', 'The framework and why it suits this study.', 400],
        'methods'       => ['Methods', 'Design, setting, population, sampling, data collection, implementation strategies, outcomes, analysis and quality assurance.', 1200],
        'engagement'    => ['Stakeholder and community engagement', 'How intended users take part in interpreting and applying the findings.', 400],
        'ethics'        => ['Ethics, data management and governance', 'Ethical considerations, confidentiality, data management and research governance.', 400],
        'workplan'      => ['Workplan and milestones', 'The full 12 months.', 400],
        'budget_notes'  => ['Budget justification', 'Link costs to activities and outputs.', 500],
        'mentorship'    => ['Mentorship and capacity development', 'What you will learn, how, and the mentor’s role.', 400],
        'dissemination' => ['Dissemination, use and sustainability', 'Knowledge-use plan, and scale-up considerations where relevant.', 400],
    ];

    /** Roughly how many words make a page of the NIMR template. */
    public const WORDS_PER_PAGE = 450;
    public const CONCEPT_MAX_PAGES = 5;
    public const PROPOSAL_MAX_PAGES = 10;

    public const BUDGET_CATEGORIES = [
        'personnel' => 'Personnel',
        'equipment' => 'Equipment',
        'research'  => 'Research costs',
        'indirect'  => 'Indirect costs',
    ];

    public const DOCUMENT_KINDS = [
        'mentor_letter'  => ['Mentor commitment letter', true],
        'cv'             => ['Applicant CV', true],
        'support_letter' => ['Institutional or site support letter', false],
    ];

    // ── Scoring (full proposal only) ────────────────────────────────────────

    /** key => [criterion, what reviewers assess, maximum score] */
    public const CRITERIA = [
        'relevance'   => ['Relevance and significance', 'Fit with priorities; importance and clarity of the implementation problem; relevance to Tanzanian health-system needs.', 20],
        'science'     => ['Scientific quality and methods', 'Research question, framework, design, methods, analysis, ethics and implementation outcomes.', 25],
        'feasibility' => ['Feasibility and value for money', 'Deliverable in 12 months; realistic milestones, team and access; proportionate, justified budget.', 20],
        'impact'      => ['Impact and use of findings', 'Practical value; stakeholder engagement; dissemination and uptake pathway.', 15],
        'development' => ['Applicant development and mentorship', 'PI potential and readiness; meaningful mentorship; skills plan.', 10],
        'innovation'  => ['Innovation and translation', 'Novelty, validation, adoption, scale-up or IP pathway where it applies. Not mandatory: do not penalise its absence.', 10],
    ];

    public const PASS_TOTAL = 70;

    /** No proposal is recommended with less than half the marks for scientific quality. */
    public const MIN_SCIENCE_SHARE = 0.5;

    /** Ties go to the stronger science score, then feasibility, then relevance. */
    public const TIE_BREAK = ['science', 'feasibility', 'relevance'];

    public static function minScience(): float
    {
        return self::CRITERIA['science'][2] * self::MIN_SCIENCE_SHARE;
    }

    public static function meetsThreshold(?float $total, ?float $science): bool
    {
        return $total !== null && $science !== null
            && $total >= self::PASS_TOTAL && $science >= self::minScience();
    }

    public static function sections(string $stage): array
    {
        return $stage === 'proposal' ? self::PROPOSAL_SECTIONS : self::CONCEPT_SECTIONS;
    }

    public static function wordCount(?string $text): int
    {
        $text = trim((string) $text);

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text));
    }

    public static function tzs(int|float|null $amount): string
    {
        return 'TZS ' . number_format((float) $amount);
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}
