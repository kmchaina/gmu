<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ResearcherProfile extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id', 'title', 'position', 'department', 'research_center', 'bio',
        'expertise_areas', 'qualifications', 'publications',
        'orcid_id', 'orcid_access_token', 'orcid_synced_at',
        'phone', 'linkedin_url', 'researchgate_url',
        'total_grants_led', 'total_grants_contributed', 'total_funding_secured',
    ];

    protected $casts = [
        'expertise_areas'  => 'array',
        'qualifications'   => 'array',
        'publications'     => 'array',
        'orcid_synced_at'  => 'datetime',
        'total_funding_secured' => 'decimal:2',
    ];

    // Never log the ORCID token
    protected function auditableFields(): array
    {
        return [
            'title', 'position', 'department', 'research_center', 'bio',
            'expertise_areas', 'qualifications', 'publications',
            'orcid_id', 'phone', 'linkedin_url', 'researchgate_url',
        ];
    }

    public function auditLabel(): string
    {
        return 'Researcher Profile: ' . ($this->user?->name ?? 'User #' . $this->user_id);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Recompute denormalised stats from actual data
    public function recalculateStats(): void
    {
        $userId = $this->user_id;

        $led = Proposal::where('lead_pi_id', $userId)
            ->whereHas('award')
            ->count();

        $contributed = ProposalTeamMember::where('user_id', $userId)
            ->whereHas('proposal.award')
            ->count();

        $funding = Award::whereHas('proposal', fn($q) => $q->where('lead_pi_id', $userId))
            ->where('status', 'active')
            ->sum('total_budget');

        $this->update([
            'total_grants_led'          => $led,
            'total_grants_contributed'  => $contributed,
            'total_funding_secured'     => $funding,
        ]);
    }
}
