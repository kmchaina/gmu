<?php

namespace Database\Seeders;

use App\Models\Seed\SeedCall;
use Illuminate\Database\Seeder;

/**
 * The FY 2026/27 Early-Career Researchers' Implementation Science Seed Grant,
 * from the call document. Deadlines and the funds available are left for GMU
 * to set under Call settings; it starts as a hidden draft.
 */
class SeedGrantCallSeeder extends Seeder
{
    public function run(): void
    {
        SeedCall::firstOrCreate(['code' => '2627'], [
            'title'                 => 'Early-Career Researchers’ Implementation Science Seed Grant',
            'fiscal_year'           => '2026/27',
            'summary'               => 'Focused, time-bound studies on how proven health interventions, services, policies or technologies can be delivered more effectively, equitably and sustainably in real Tanzanian settings.',
            'min_amount'            => 2_000_000,
            'max_amount'            => 10_000_000,
            'envelope'              => null,
            'duration_months'       => 12,
            'equipment_cap_percent' => 20,
            'concept_deadline'      => null,
            'proposal_deadline'     => null,
            'status'                => 'draft',
        ]);
    }
}
