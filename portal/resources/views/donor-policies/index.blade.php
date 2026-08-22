@extends('adminlte::page')

@section('title', 'Donor Policies')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-book mr-2"></i>Donor Policies & Guidelines</h1>
        <a href="{{ route('donor-policies.create') }}" class="btn btn-primary">
            <i class="fas fa-upload mr-1"></i> Upload Policy
        </a>
    </div>
@endsection

@section('content')

<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filter</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ route('donor-policies.index') }}" class="form-inline flex-wrap" style="gap:10px;">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="Search title..." style="min-width:200px;">
            <select name="donor_id" class="form-control form-control-sm">
                <option value="">All Donors</option>
                @foreach($donors as $donor)
                    <option value="{{ $donor->id }}" {{ request('donor_id') == $donor->id ? 'selected' : '' }}>
                        {{ $donor->acronym ?? $donor->name }}
                    </option>
                @endforeach
            </select>
            <select name="policy_type" class="form-control form-control-sm">
                <option value="">All Types</option>
                <option value="grant_guidelines"    {{ request('policy_type') === 'grant_guidelines'    ? 'selected' : '' }}>Grant Guidelines</option>
                <option value="reporting_template"  {{ request('policy_type') === 'reporting_template'  ? 'selected' : '' }}>Reporting Template</option>
                <option value="budget_template"     {{ request('policy_type') === 'budget_template'     ? 'selected' : '' }}>Budget Template</option>
                <option value="compliance_policy"   {{ request('policy_type') === 'compliance_policy'   ? 'selected' : '' }}>Compliance Policy</option>
                <option value="other"               {{ request('policy_type') === 'other'               ? 'selected' : '' }}>Other</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Search</button>
            <a href="{{ route('donor-policies.index') }}" class="btn btn-sm btn-default">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">{{ $policies->total() }} Policy Document(s)</h3></div>
    <div class="card-body p-0">
        @if($policies->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fas fa-book fa-3x mb-3"></i>
                <h5>No policy documents found.</h5>
            </div>
        @else
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Title</th>
                    <th>Donor</th>
                    <th>Type</th>
                    <th>Ver.</th>
                    <th>Effective</th>
                    <th>Uploaded By</th>
                    <th width="80">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($policies as $policy)
                @php
                    $typeLabels = [
                        'grant_guidelines'   => ['label' => 'Grant Guidelines',    'color' => 'primary'],
                        'reporting_template' => ['label' => 'Reporting Template',  'color' => 'info'],
                        'budget_template'    => ['label' => 'Budget Template',     'color' => 'success'],
                        'compliance_policy'  => ['label' => 'Compliance Policy',   'color' => 'warning'],
                        'other'              => ['label' => 'Other',               'color' => 'secondary'],
                    ];
                    $t = $typeLabels[$policy->policy_type] ?? ['label' => ucfirst($policy->policy_type), 'color' => 'secondary'];
                @endphp
                <tr>
                    <td>
                        <a href="{{ Storage::url($policy->file_path) }}" target="_blank">
                            <i class="fas fa-file mr-1 text-muted"></i>{{ $policy->title }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ route('donors.show', $policy->donor) }}">
                            <span class="badge badge-secondary">{{ $policy->donor->acronym ?? $policy->donor->name }}</span>
                        </a>
                    </td>
                    <td><span class="badge badge-{{ $t['color'] }}">{{ $t['label'] }}</span></td>
                    <td>v{{ $policy->version }}</td>
                    <td>{{ $policy->effective_date ? $policy->effective_date->format('d M Y') : '-' }}</td>
                    <td>{{ $policy->uploadedBy->name }}</td>
                    <td>
                        <a href="{{ Storage::url($policy->file_path) }}" target="_blank"
                           class="btn btn-xs btn-outline-primary" title="Download">
                            <i class="fas fa-download"></i>
                        </a>
                        <a href="{{ route('donor-policies.edit', $policy) }}" class="btn btn-xs btn-outline-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @if($policies->hasPages())
    <div class="card-footer">{{ $policies->links() }}</div>
    @endif
</div>

@endsection
