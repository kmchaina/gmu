<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Donor extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'acronym', 'type', 'country', 'website', 'contact_email', 'notes'];

    public function auditLabel(): string
    {
        return 'Donor: ' . ($this->name ?? '#' . $this->getKey());
    }

    public function fundingCalls() { return $this->hasMany(FundingCall::class); }
    public function awards() { return $this->hasMany(Award::class); }
    public function policies() { return $this->hasMany(DonorPolicy::class); }
}
