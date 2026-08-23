@extends('layouts.gmu')

@section('title', 'Donor policies & guidelines')
@section('crumb', 'Library')

@section('content')
<x-ui.page-head
    title="Donor policies &amp; guidelines"
    :lede="$policies->total() . ' ' . Str::plural('document', $policies->total()) . ' · what each funder requires, in one place'">
    <x-slot:actions>
        @can('create', App\Models\DonorPolicy::class)
            <a href="{{ route('donor-policies.create') }}" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</a>
        @endcan
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">
    <div class="row-between">
        <div class="chips">
            <a href="{{ route('donor-policies.index') }}" class="chip {{ ! request('policy_type') ? 'is-active' : '' }}">All</a>
            @foreach(['grant_guidelines' => 'Guidelines', 'reporting_template' => 'Reporting templates', 'budget_template' => 'Budget templates', 'compliance_policy' => 'Compliance', 'other' => 'Other'] as $value => $label)
                <a href="{{ route('donor-policies.index', ['policy_type' => $value]) }}"
                   class="chip {{ request('policy_type') === $value ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="row" style="gap:8px;">
            <select name="donor_id" class="select" style="width:190px;" onchange="this.form.submit()">
                <option value="">All donors</option>
                @foreach($donors as $donor)
                    <option value="{{ $donor->id }}" @selected(request('donor_id') == $donor->id)>{{ $donor->acronym ?? $donor->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="grid grid-cards">
        @forelse($policies as $policy)
            <div class="card">
                <div class="card-body" style="display:flex; flex-direction:column; gap:14px;">
                    <div class="row-between" style="align-items:flex-start;">
                        <div>
                            <div class="eyebrow">{{ $policy->donor?->acronym ?? $policy->donor?->name }}</div>
                            <a href="{{ route('donor-policies.show', $policy) }}"
                               style="display:block; margin-top:7px; font-size:14.5px; font-weight:600; line-height:1.35; color:var(--ink);">
                                {{ $policy->title }}
                            </a>
                        </div>
                        <x-ui.pill>v{{ $policy->version }}</x-ui.pill>
                    </div>

                    <div class="row" style="gap:10px;">
                        <x-ui.pill tone="info">{{ Str::headline($policy->policy_type) }}</x-ui.pill>
                        @if($policy->effective_date)
                            <span class="tiny muted-3">Effective {{ $policy->effective_date->format('M Y') }}</span>
                        @endif
                    </div>

                    @if($policy->notes)<p class="small muted">{{ Str::limit($policy->notes, 110) }}</p>@endif

                    <div class="row-between" style="padding-top:12px; border-top:1px solid var(--line-soft);">
                        <span class="tiny muted-3">{{ $policy->file_name }}</span>
                        <div class="row" style="gap:6px;">
                            <a href="{{ route('donor-policies.show', $policy) }}" class="btn btn-sm"><i class="fas fa-download"></i> Open</a>
                            @can('update', $policy)
                                <a href="{{ route('donor-policies.edit', $policy) }}" class="btn btn-sm"><i class="fas fa-pen"></i></a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card" style="grid-column:1/-1;">
                <x-ui.empty icon="fa-book" title="No guidelines filed">
                    Donor guidelines, reporting and budget templates belong here — the reference a PI needs before
                    writing, and the GMU needs before reviewing.
                </x-ui.empty>
            </div>
        @endforelse
    </div>

    <div class="pagination-wrap">{{ $policies->links() }}</div>
</div>
@endsection
