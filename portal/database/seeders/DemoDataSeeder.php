<?php

namespace Database\Seeders;

use App\Models\Award;
use App\Models\CallInterest;
use App\Models\Donor;
use App\Models\FundingCall;
use App\Models\LibraryItem;
use App\Models\Proposal;
use App\Models\ProposalApproval;
use App\Models\ProposalReview;
use App\Models\Report;
use App\Models\ResearcherProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\ComplianceScheduleGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A working NIMR portfolio for user acceptance testing (audit finding M19).
 *
 * The database held two accounts and nothing else, so the lifecycle had never
 * been exercised end to end and no screen had ever been seen with real data.
 * This creates a portfolio a Coordinator can walk through: calls at different
 * stages, proposals across the pipeline, live awards with reporting schedules,
 * an overdue report, an ethics approval about to lapse.
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * Everything here is demonstration data. To clear it and start clean:
 *   php artisan migrate:fresh --seed
 */
class DemoDataSeeder extends Seeder
{
    private const PASSWORD = 'GmuDemo!2026';

    public function run(): void
    {
        $this->call(RolesAndAdminSeeder::class);

        $people  = $this->people();
        $donors  = $this->donors();
        $calls   = $this->calls($donors, $people);
        $this->interests($calls, $people);
        $proposals = $this->proposals($calls, $people);
        $this->awards($proposals, $people);
        $this->library($people);

        $this->command?->newLine();
        $this->command?->info('Demo portfolio created.');
        $this->command?->line('  Sign in with any of these; the password is: ' . self::PASSWORD);

        foreach ($people as $key => $user) {
            $this->command?->line(sprintf('    %-22s %s', $user->role?->display_name ?? $key, $user->email));
        }

        $this->command?->newLine();
    }

    /** @return array<string, User> */
    private function people(): array
    {
        $definitions = [
            'planning'   => ['Grace Mwakalinga',   'planning@nimr.or.tz',  'planning_officer',   'Grants Management Unit'],
            'finance'    => ['Joseph Kimaro',      'finance@nimr.or.tz',   'finance_officer',    'Finance'],
            'legal'      => ['Neema Shirima',      'legal@nimr.or.tz',     'legal_officer',      'Legal & Compliance'],
            'scientist'  => ['Dr Amani Massawe',   'scientist@nimr.or.tz', 'research_scientist', 'Muhimbili Centre'],
            'drcp'       => ['Prof Rehema Ulomi',  'drcp@nimr.or.tz',      'drcp',               'Directorate of Research Coordination'],
            'dg'         => ['Prof Said Mwanjala', 'dg@nimr.or.tz',        'dg',                 "Director General's Office"],
            'pi_malaria' => ['Dr Fatuma Mchome',   'f.mchome@nimr.or.tz',  'pi',                 'Amani Centre'],
            'pi_tb'      => ['Dr Baraka Ngowi',    'b.ngowi@nimr.or.tz',   'pi',                 'Kibong\'oto Centre'],
            'pi_hiv'     => ['Dr Zawadi Lyimo',    'z.lyimo@nimr.or.tz',   'pi',                 'Mwanza Centre'],
        ];

        $expertise = [
            'pi_malaria' => ['Malaria', 'Vector control', 'Epidemiology'],
            'pi_tb'      => ['Tuberculosis', 'Drug resistance', 'Clinical trials'],
            'pi_hiv'     => ['HIV', 'Implementation science', 'Adolescent health'],
            'scientist'  => ['Epidemiology', 'Biostatistics', 'One Health'],
        ];

        $people = [];

        foreach ($definitions as $key => [$name, $email, $role, $department]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'            => $name,
                    'password'        => Hash::make(self::PASSWORD),
                    'role_id'         => Role::where('name', $role)->value('id'),
                    'department'      => $department,
                    'research_center' => $department,
                    'is_active'       => true,
                ]
            );

            if (isset($expertise[$key])) {
                ResearcherProfile::firstOrCreate(['user_id' => $user->id], [
                    'title'           => str_starts_with($name, 'Prof') ? 'Prof.' : 'Dr.',
                    'position'        => 'Research Scientist',
                    'department'      => $department,
                    'research_center' => $department,
                    'bio'             => 'Research scientist at NIMR working on ' . strtolower($expertise[$key][0]) . '.',
                    'expertise_areas' => $expertise[$key],
                    'qualifications'  => [
                        ['degree' => 'PhD', 'field' => $expertise[$key][0], 'institution' => 'MUHAS', 'year' => 2014],
                        ['degree' => 'MSc', 'field' => 'Public Health', 'institution' => 'University of Dar es Salaam', 'year' => 2009],
                    ],
                    'publications'    => [
                        ['title' => 'Trends in ' . $expertise[$key][0] . ' in northern Tanzania', 'journal' => 'Tanzania Journal of Health Research', 'year' => 2023],
                    ],
                ]);
            }

            $people[$key] = $user->load('role');
        }

        $people['coordinator'] = User::whereHas('role', fn($q) => $q->where('name', 'coordinator'))->first()->load('role');

        return $people;
    }

    /** @return array<string, Donor> */
    private function donors(): array
    {
        $rows = [
            'nih'      => ['National Institutes of Health', 'NIH', 'government', 'United States'],
            'wellcome' => ['Wellcome Trust', 'Wellcome', 'foundation', 'United Kingdom'],
            'gates'    => ['Bill & Melinda Gates Foundation', 'BMGF', 'foundation', 'United States'],
            'edctp'    => ['European & Developing Countries Clinical Trials Partnership', 'EDCTP', 'multilateral', 'Netherlands'],
            'gfund'    => ['The Global Fund', 'GFATM', 'multilateral', 'Switzerland'],
        ];

        $donors = [];

        foreach ($rows as $key => [$name, $acronym, $type, $country]) {
            $donors[$key] = Donor::firstOrCreate(['name' => $name], [
                'acronym'       => $acronym,
                'type'          => $type,
                'country'       => $country,
                'contact_email' => 'grants@' . strtolower($acronym) . '.org',
            ]);
        }

        return $donors;
    }

    /** @return array<string, FundingCall> */
    private function calls(array $donors, array $people): array
    {
        $rows = [
            'vector'   => ['Malaria vector surveillance in East Africa', 'nih', 'Malaria', 30, 'open', 1200000, 'USD'],
            'tb'       => ['Drug-resistant TB: diagnostics and treatment outcomes', 'edctp', 'Tuberculosis', 12, 'open', 900000, 'EUR'],
            'adol'     => ['Adolescent HIV care and retention', 'gates', 'HIV', 55, 'open', 750000, 'USD'],
            'onehealth'=> ['One Health surveillance of zoonotic disease', 'wellcome', 'One Health', 6, 'open', 640000, 'GBP'],
            'systems'  => ['Health systems strengthening in rural districts', 'gfund', 'Health systems', -20, 'closed', 2000000, 'USD'],
        ];

        $calls = [];

        foreach ($rows as $key => [$title, $donorKey, $theme, $daysToDeadline, $status, $budget, $currency]) {
            $calls[$key] = FundingCall::firstOrCreate(['title' => $title], [
                'donor_id'         => $donors[$donorKey]->id,
                'posted_by'        => $people['planning']->id,
                'description'      => 'Applications are invited for research addressing ' . strtolower($theme)
                                      . ' in Tanzania and the wider East African region.',
                'theme'            => $theme,
                'eligibility'      => 'Open to research institutions in sub-Saharan Africa. NIMR submits one institutional proposal per call.',
                'estimated_budget' => $budget,
                'currency'         => $currency,
                'open_date'        => now()->subDays(max(1, 40 - $daysToDeadline)),
                'deadline'         => now()->addDays($daysToDeadline),
                'initiation_type'  => 'gmu_posted',
                'status'           => $status,
            ]);
        }

        return $calls;
    }

    private function interests(array $calls, array $people): void
    {
        $rows = [
            ['vector', 'pi_malaria', 'selected'],
            ['vector', 'scientist',  'not_selected'],
            ['tb',     'pi_tb',      'selected'],
            ['adol',   'pi_hiv',     'pending'],
            ['adol',   'scientist',  'pending'],
        ];

        foreach ($rows as [$callKey, $personKey, $status]) {
            CallInterest::firstOrCreate(
                ['funding_call_id' => $calls[$callKey]->id, 'user_id' => $people[$personKey]->id],
                [
                    'motivation'          => 'This call sits squarely within my current research programme and the centre has the field capacity to deliver it.',
                    'relevant_experience' => 'Ten years of field research in this area, including two previously funded studies as lead investigator.',
                    'status'              => $status,
                    'decided_at'          => $status === 'pending' ? null : now()->subDays(5),
                    'decided_by'          => $status === 'pending' ? null : $people['coordinator']->id,
                ]
            );
        }
    }

    /** @return array<string, Proposal> */
    private function proposals(array $calls, array $people): array
    {
        $rows = [
            // key            call        lead PI       status              budget   currency
            'vector_award' => ['vector',   'pi_malaria', 'awarded',          980000,  'USD'],
            'tb_award'     => ['tb',       'pi_tb',      'awarded',          760000,  'EUR'],
            'adol_review'  => ['adol',     'pi_hiv',     'internal_review',  610000,  'USD'],
            'onehealth'    => ['onehealth','scientist',  'under_development',420000,  'GBP'],
            'systems_dec'  => ['systems',  'pi_hiv',     'declined',         1500000, 'USD'],
            'concept'      => ['adol',     'pi_malaria', 'concept',          300000,  'USD'],
        ];

        $proposals = [];

        foreach ($rows as $key => [$callKey, $piKey, $status, $budget, $currency]) {
            $proposal = Proposal::firstOrCreate(
                ['funding_call_id' => $calls[$callKey]->id, 'lead_pi_id' => $people[$piKey]->id, 'title' => $this->proposalTitle($callKey, $key)],
                [
                    'assigned_officer_id'    => $people['planning']->id,
                    'abstract'               => 'This study will generate the evidence NIMR needs to advise the Ministry of Health on '
                                                . strtolower($calls[$callKey]->theme) . ' policy, working across three research centres.',
                    'initiation_type'        => 'gmu_initiated',
                    'status'                 => $status,
                    'target_submission_date' => $calls[$callKey]->deadline,
                    'submitted_at'           => in_array($status, ['awarded', 'declined', 'submitted'], true) ? now()->subMonths(4) : null,
                    'outcome_date'           => in_array($status, ['awarded', 'declined'], true) ? now()->subMonths(2) : null,
                    'outcome_notes'          => $status === 'declined' ? 'Not funded — the donor cited a crowded portfolio in this area.' : null,
                    'requested_budget'       => $budget,
                    'currency'               => $currency,
                ]
            );

            $this->reviews($proposal, $people, $status);
            $proposals[$key] = $proposal;
        }

        // The proposal in review has two of three tracks done — a realistic
        // half-finished state for the reviewer screens.
        return $proposals;
    }

    private function proposalTitle(string $callKey, string $key): string
    {
        return match ($key) {
            'vector_award' => 'Insecticide resistance mapping in Kigoma and Kagera',
            'tb_award'     => 'Rapid molecular diagnosis of drug-resistant TB in referral hospitals',
            'adol_review'  => 'Peer-led retention in adolescent HIV care in Mwanza',
            'onehealth'    => 'Zoonotic spillover surveillance at livestock-wildlife interfaces',
            'systems_dec'  => 'District health information systems strengthening',
            default        => 'Community perceptions of malaria prevention in coastal districts',
        };
    }

    private function reviews(Proposal $proposal, array $people, string $status): void
    {
        $tracks = [
            'technical'  => $people['scientist'],
            'compliance' => $people['legal'],
            'budget'     => $people['finance'],
        ];

        $complete = in_array($status, ['awarded', 'declined', 'approved', 'submitted'], true);

        foreach ($tracks as $track => $reviewer) {
            // The in-review proposal deliberately still has budget outstanding.
            $done = $complete || ($status === 'internal_review' && $track !== 'budget');

            ProposalReview::firstOrCreate(
                ['proposal_id' => $proposal->id, 'review_type' => $track],
                [
                    'reviewer_id' => $done ? $reviewer->id : null,
                    'status'      => $done ? 'approved' : 'pending',
                    'comments'    => $done ? 'Reviewed against the donor guidelines and NIMR research priorities. No blocking concerns.' : null,
                    'score'       => $done ? 8 : null,
                    'reviewed_at' => $done ? now()->subMonths(5) : null,
                ]
            );
        }

        if ($complete) {
            foreach (['drcp' => $people['drcp'], 'dg' => $people['dg']] as $level => $officer) {
                ProposalApproval::firstOrCreate(
                    ['proposal_id' => $proposal->id, 'level' => $level],
                    [
                        'status'                   => 'approved',
                        'decided_by'               => $officer->id,
                        'decided_at'               => now()->subMonths(5),
                        'comments'                 => 'Cleared institutionally.',
                        'letter_of_support_issued' => $level === 'dg',
                    ]
                );
            }
        }
    }

    private function awards(array $proposals, array $people): void
    {
        $generator = app(ComplianceScheduleGenerator::class);

        $rows = [
            'vector_award' => ['NIH-1R01-2024-0917', 'quarterly',   980000, 'USD', -14, 22],
            'tb_award'     => ['EDCTP-RIA-2024-118', 'semi_annual', 760000, 'EUR', -8,  16],
        ];

        foreach ($rows as $proposalKey => [$contract, $frequency, $budget, $currency, $startMonths, $endMonths]) {
            $proposal = $proposals[$proposalKey];

            $award = Award::firstOrCreate(['contract_number' => $contract], [
                'proposal_id'         => $proposal->id,
                'donor_id'            => $proposal->fundingCall->donor_id,
                'title'               => $proposal->title,
                'start_date'          => now()->addMonths($startMonths)->startOfMonth(),
                'end_date'            => now()->addMonths($endMonths)->endOfMonth(),
                'total_budget'        => $budget,
                'currency'            => $currency,
                'status'              => 'active',
                'reporting_frequency' => $frequency,
                'kickoff_date'        => now()->addMonths($startMonths)->addDays(10),
                'notes'               => 'Multi-centre study coordinated from the lead centre.',
            ]);

            $generator->generate($award);

            $categories = [
                ['personnel', 0.45], ['equipment', 0.15], ['travel', 0.1],
                ['consumables', 0.15], ['indirect', 0.15],
            ];

            foreach ($categories as [$category, $share]) {
                $allocated = round($budget * $share, 2);

                $award->budgets()->firstOrCreate(['category' => $category], [
                    'allocated_amount' => $allocated,
                    'spent_amount'     => round($allocated * (0.3 + (crc32($category) % 40) / 100), 2),
                    'currency'         => $currency,
                ]);
            }

            $award->ethicsRenewals()->firstOrCreate(
                ['ethics_number' => 'NIMR/HQ/R.8a/Vol.IX/' . random_int(3000, 3999)],
                [
                    'issuing_body'   => 'NIMR Medical Research Coordinating Committee',
                    'approval_date'  => now()->addMonths($startMonths),
                    // One protocol is inside the renewal window, which is what the
                    // risk engine and the ethics reminders exist to catch.
                    'expiry_date'    => $proposalKey === 'vector_award' ? now()->addDays(24) : now()->addMonths(9),
                    'renewal_status' => 'valid',
                ]
            );

            $award->monitoringVisits()->firstOrCreate(
                ['visit_date' => now()->subMonths(3)->toDateString()],
                [
                    'conducted_by'    => $people['planning']->id,
                    'location'        => 'Field site and centre finance office',
                    'findings'        => 'Study procedures followed the approved protocol. Expenditure records were complete and reconciled to the ledger.',
                    'recommendations' => 'Tighten the filing of consent forms and submit the next financial report a week early.',
                ]
            );
        }

        // These awards started months ago, so their earlier reporting periods
        // should read as filed rather than as a wall of overdue items.
        $leads = [
            'NIH-1R01-2024-0917' => $people['pi_malaria'],
            'EDCTP-RIA-2024-118' => $people['pi_tb'],
        ];

        foreach ($leads as $contract => $lead) {
            Report::whereHas('award', fn($q) => $q->where('contract_number', $contract))
                ->where('status', 'pending')
                ->whereDate('due_date', '<', now())
                ->get()
                ->each(function (Report $report) use ($lead, $people) {
                    $report->update([
                        'status'         => 'approved',
                        'submitted_by'   => $lead->id,
                        'submitted_date' => $report->due_date->copy()->subDays(3),
                        'verified_by'    => $people['finance']->id,
                        'verified_at'    => $report->due_date->copy()->addDays(2),
                        'gmu_comments'   => 'Verified against the ledger and submitted to the donor.',
                    ]);
                });
        }

        // One report left overdue, and one due shortly, so the traffic light,
        // the risk engine and the reminder job all have something real to work
        // with on the dashboard.
        $next = Report::whereHas('award', fn($q) => $q->where('contract_number', 'NIH-1R01-2024-0917'))
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->get();

        $next->get(0)?->update(['due_date' => now()->subDays(11)->toDateString()]);
        $next->get(1)?->update(['due_date' => now()->addDays(9)->toDateString()]);
    }

    private function library(array $people): void
    {
        $items = [
            ['NIMR institutional capability statement', 'capabilities_statement',
             'The National Institute for Medical Research is Tanzania\'s principal public health research institution, established under the NIMR Act [Cap. 59 R.E. 2023]. NIMR operates research centres across the country, coordinating medical research, maintaining national research registration, and advising the Ministry of Health on evidence for policy.'],
            ['Facilities and equipment — molecular laboratories', 'facilities_equipment',
             'NIMR centres maintain BSL-2 molecular laboratories with real-time PCR platforms, sequencing capacity through partner institutions, cold chain to -80C, and secure data management infrastructure administered by the institute\'s IT function.'],
            ['Data management and sharing plan', 'data_management',
             'Study data are collected on encrypted devices, transferred daily to the institutional server, and stored for the period required by the funder and by national regulation. De-identified datasets are shared on request under a data transfer agreement approved by the Medical Research Coordinating Committee.'],
            ['Ethical framework and approvals', 'ethical_framework',
             'All NIMR studies are reviewed by the Medical Research Coordinating Committee. Approvals are renewed annually, adverse events are reported within the timelines set by the committee, and informed consent is administered in Kiswahili and the local language of the study community.'],
        ];

        foreach ($items as [$title, $category, $content]) {
            LibraryItem::firstOrCreate(['title' => $title], [
                'created_by'       => $people['coordinator']->id,
                'category'         => $category,
                'content'          => $content,
                'usage_notes'      => 'Adapt the wording to the donor template; check the figures before each submission.',
                'tags'             => ['institutional', 'boilerplate'],
                'funder_relevance' => 'NIH,Wellcome,EDCTP,Gates',
                'is_approved'      => true,
            ]);
        }
    }
}
