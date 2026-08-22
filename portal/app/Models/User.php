<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'phone', 'department', 'research_center', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role?->name, $roles);
    }

    public function isCoordinator(): bool { return $this->hasRole('coordinator'); }
    public function isPI(): bool { return $this->hasRole('pi'); }
    public function isFinanceOfficer(): bool { return $this->hasRole('finance_officer'); }
    public function isLegalOfficer(): bool { return $this->hasRole('legal_officer'); }
    public function isPlanningOfficer(): bool { return $this->hasRole('planning_officer'); }
    public function isResearchScientist(): bool { return $this->hasRole('research_scientist'); }
    public function isSecretary(): bool { return $this->hasRole('secretary'); }

    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'lead_pi_id');
    }

    public function assignedProposals()
    {
        return $this->hasMany(Proposal::class, 'assigned_officer_id');
    }

    public function callInterests()
    {
        return $this->hasMany(CallInterest::class);
    }

    public function proposalReviews()
    {
        return $this->hasMany(ProposalReview::class, 'reviewer_id');
    }

    public function notifications()
    {
        return $this->hasMany(GmuNotification::class);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->whereNull('read_at')->count();
    }

    public function researcherProfile()
    {
        return $this->hasOne(ResearcherProfile::class);
    }

    public function fcoiDisclosures()
    {
        return $this->hasMany(FcoiDisclosure::class);
    }
}
