@extends('adminlte::page')

@section('title', 'Donors & Funders')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-building mr-2"></i>Donors & Funders</h1>
        <a href="{{ route('donors.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Add Donor
        </a>
    </div>
@endsection

@section('content')

{{-- Filters --}}
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filter</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ route('donors.index') }}" class="form-inline flex-wrap" style="gap:10px;">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="Search name, acronym, country..." style="min-width:240px;">
            <select name="type" class="form-control form-control-sm">
                <option value="">All Types</option>
                <option value="bilateral"     {{ request('type') === 'bilateral'     ? 'selected' : '' }}>Bilateral</option>
                <option value="multilateral"  {{ request('type') === 'multilateral'  ? 'selected' : '' }}>Multilateral</option>
                <option value="foundation"    {{ request('type') === 'foundation'    ? 'selected' : '' }}>Foundation</option>
                <option value="government"    {{ request('type') === 'government'    ? 'selected' : '' }}>Government</option>
                <option value="private"       {{ request('type') === 'private'       ? 'selected' : '' }}>Private</option>
                <option value="other"         {{ request('type') === 'other'         ? 'selected' : '' }}>Other</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Search</button>
            <a href="{{ route('donors.index') }}" class="btn btn-sm btn-default">Reset</a>
        </form>
    </div>
</div>

{{-- Donors Table --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $donors->total() }} Donor(s)</h3>
    </div>
    <div class="card-body p-0">
        @if($donors->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fas fa-building fa-3x mb-3"></i>
                <h5>No donors found.</h5>
                <a href="{{ route('donors.create') }}" class="btn btn-primary mt-2">Add First Donor</a>
            </div>
        @else
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th width="40">#</th>
                    <th>Name</th>
                    <th>Acronym</th>
                    <th>Type</th>
                    <th>Country</th>
                    <th class="text-center" width="80">Calls</th>
                    <th class="text-center" width="80">Awards</th>
                    <th width="100">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($donors as $i => $donor)
                <tr>
                    <td>{{ $donors->firstItem() + $i }}</td>
                    <td>
                        <a href="{{ route('donors.show', $donor) }}" class="font-weight-bold text-dark">
                            {{ $donor->name }}
                        </a>
                    </td>
                    <td>
                        @if($donor->acronym)
                            <span class="badge badge-secondary">{{ $donor->acronym }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $typeColors = [
                                'bilateral'    => 'primary',
                                'multilateral' => 'info',
                                'foundation'   => 'success',
                                'government'   => 'warning',
                                'private'      => 'secondary',
                                'other'        => 'light',
                            ];
                        @endphp
                        <span class="badge badge-{{ $typeColors[$donor->type] ?? 'secondary' }}">
                            {{ ucfirst($donor->type) }}
                        </span>
                    </td>
                    <td>{{ $donor->country ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $donor->funding_calls_count }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-success">{{ $donor->awards_count }}</span>
                    </td>
                    <td>
                        <a href="{{ route('donors.show', $donor) }}" class="btn btn-xs btn-outline-primary" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('donors.edit', $donor) }}" class="btn btn-xs btn-outline-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @if($donors->hasPages())
    <div class="card-footer">{{ $donors->links() }}</div>
    @endif
</div>

@endsection
