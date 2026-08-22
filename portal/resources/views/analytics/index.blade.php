@extends('adminlte::page')

@section('title', 'Analytics')

@section('content_header')
    <h1><i class="fas fa-chart-bar mr-2"></i>Portfolio Analytics</h1>
@endsection

@section('content')

{{-- ── KPI Row ──────────────────────────────────────────────────────── --}}
<div class="row">
    @php
    $kpiCards = [
        ['label'=>'Active Awards',      'value'=> $kpis['active_awards'],      'icon'=>'fas fa-trophy',       'color'=>'success'],
        ['label'=>'Total Portfolio',    'value'=> 'USD '.number_format($kpis['total_funding']), 'icon'=>'fas fa-dollar-sign','color'=>'primary'],
        ['label'=>'Success Rate',       'value'=> $kpis['success_rate'].'%',   'icon'=>'fas fa-percentage',   'color'=>'info'],
        ['label'=>'Open Calls',         'value'=> $kpis['open_calls'],         'icon'=>'fas fa-bullhorn',     'color'=>'warning'],
        ['label'=>'Overdue Reports',    'value'=> $kpis['overdue_reports'],    'icon'=>'fas fa-exclamation-circle','color'=>'danger'],
        ['label'=>'Compliance Overdue', 'value'=> $kpis['compliance_overdue'],'icon'=>'fas fa-calendar-times','color'=>'danger'],
        ['label'=>'Ethics Expiring',    'value'=> $kpis['ethics_expiring_soon'],'icon'=>'fas fa-shield-alt', 'color'=>'warning'],
        ['label'=>'Total Proposals',    'value'=> $kpis['proposals_total'],    'icon'=>'fas fa-file-alt',     'color'=>'secondary'],
    ];
    @endphp

    @foreach($kpiCards as $card)
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="info-box shadow-sm mb-3">
            <span class="info-box-icon bg-{{ $card['color'] }} elevation-1">
                <i class="{{ $card['icon'] }}"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">{{ $card['label'] }}</span>
                <span class="info-box-number">{{ $card['value'] }}</span>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Row 2: Awards by Status + Proposals by Status ──────────────── --}}
<div class="row">
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Awards by Status</h3></div>
            <div class="card-body" style="position:relative;height:250px">
                <canvas id="awardStatusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Proposals by Status</h3></div>
            <div class="card-body" style="position:relative;height:250px">
                <canvas id="proposalStatusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title">Report Timeliness (12 mo.)</h3></div>
            <div class="card-body d-flex flex-column justify-content-center" style="height:250px">
                @if($reportTimeliness['total'] > 0)
                    <canvas id="timelinessChart" style="max-height:180px"></canvas>
                    <div class="text-center mt-2 small text-muted">
                        {{ $reportTimeliness['on_time'] }} on-time &nbsp;|&nbsp; {{ $reportTimeliness['late'] }} late
                        &nbsp;of&nbsp; {{ $reportTimeliness['total'] }} submitted
                    </div>
                @else
                    <p class="text-center text-muted">No submitted reports yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Row 3: Awards per Year + Funding by Donor ──────────────────── --}}
<div class="row">
    <div class="col-md-5">
        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title">New Awards per Year</h3></div>
            <div class="card-body" style="position:relative;height:260px">
                <canvas id="awardsYearChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Funding by Donor (Top 8)</h3></div>
            <div class="card-body" style="position:relative;height:260px">
                <canvas id="donorFundingChart"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- ── Row 4: Budget Utilisation ────────────────────────────────────── --}}
<div class="card card-outline card-danger">
    <div class="card-header"><h3 class="card-title">Budget Utilisation — Active Awards</h3></div>
    <div class="card-body p-0">
        @if(count($budgetUtilisation))
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th class="pl-3">Award</th>
                    <th>Allocated</th>
                    <th>Spent</th>
                    <th>Remaining</th>
                    <th style="width:200px">Utilisation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($budgetUtilisation as $row)
                <tr>
                    <td class="pl-3 font-weight-bold">{{ $row['label'] }}</td>
                    <td>{{ number_format($row['allocated'], 0) }}</td>
                    <td>{{ number_format($row['spent'], 0) }}</td>
                    <td class="{{ $row['spent'] > $row['allocated'] ? 'text-danger font-weight-bold' : '' }}">
                        {{ number_format($row['allocated'] - $row['spent'], 0) }}
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress flex-grow-1 mr-2" style="height:8px">
                                <div class="progress-bar bg-{{ $row['light'] === 'red' ? 'danger' : ($row['light'] === 'yellow' ? 'warning' : 'success') }}"
                                     style="width:{{ min($row['pct'], 100) }}%"></div>
                            </div>
                            <span class="small font-weight-bold
                                {{ $row['pct'] > 100 ? 'text-danger' : ($row['pct'] > 85 ? 'text-warning' : 'text-success') }}">
                                {{ $row['pct'] }}%
                            </span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <p class="text-center text-muted py-4">No active awards with budget data yet.</p>
        @endif
    </div>
</div>

{{-- ── Row 5: Recent Activity ───────────────────────────────────────── --}}
<div class="card card-outline card-light">
    <div class="card-header">
        <h3 class="card-title">Recent System Activity</h3>
        <div class="card-tools">
            <a href="{{ route('admin.activity-log.index') }}" class="btn btn-xs btn-outline-secondary">Full Log</a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <tbody>
                @foreach($recentActivity as $log)
                <tr>
                    <td style="width:140px" class="text-muted small pl-3">{{ $log->created_at->diffForHumans() }}</td>
                    <td style="width:130px">{{ $log->user?->name ?? 'System' }}</td>
                    <td>
                        @php $b = match($log->action){ 'created'=>'success','updated'=>'info','deleted'=>'danger',default=>'secondary' }; @endphp
                        <span class="badge badge-{{ $b }}">{{ $log->action }}</span>
                        {{ $log->description }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const palette = ['#003366','#0077b6','#00b4d8','#90e0ef','#48cae4','#023e8a','#0096c7','#ade8f4'];
const danger  = '#e74c3c', warning = '#f39c12', success = '#27ae60', info = '#3498db';

// Awards by Status
new Chart(document.getElementById('awardStatusChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($awardsByStatus->keys()->map(fn($k) => ucfirst($k))->values()) !!},
        datasets: [{ data: {!! json_encode($awardsByStatus->values()) !!}, backgroundColor: palette }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}} }
});

// Proposals by Status
new Chart(document.getElementById('proposalStatusChart'), {
    type: 'pie',
    data: {
        labels: {!! json_encode($proposalsByStatus->keys()->map(fn($k) => ucwords(str_replace('_',' ',$k)))->values()) !!},
        datasets: [{ data: {!! json_encode($proposalsByStatus->values()) !!}, backgroundColor: palette }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}} }
});

// Timeliness
@if($reportTimeliness['total'] > 0)
new Chart(document.getElementById('timelinessChart'), {
    type: 'doughnut',
    data: {
        labels: ['On Time', 'Late'],
        datasets: [{ data: [{{ $reportTimeliness['on_time'] }}, {{ $reportTimeliness['late'] }}],
                     backgroundColor: [success, danger] }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}} }
});
@endif

// Awards per Year
new Chart(document.getElementById('awardsYearChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($awardsPerYear->keys()) !!},
        datasets: [{
            label: 'Awards',
            data: {!! json_encode($awardsPerYear->values()) !!},
            backgroundColor: '#003366'
        }]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{legend:{display:false}},
        scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } }
    }
});

// Funding by Donor
new Chart(document.getElementById('donorFundingChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode(collect($fundingByDonor)->pluck('label')) !!},
        datasets: [{
            label: 'Total Funding',
            data: {!! json_encode(collect($fundingByDonor)->pluck('amount')) !!},
            backgroundColor: palette
        }]
    },
    options: {
        indexAxis: 'y',
        responsive:true, maintainAspectRatio:false,
        plugins:{legend:{display:false}},
        scales:{ x:{ beginAtZero:true } }
    }
});
</script>
@endsection
