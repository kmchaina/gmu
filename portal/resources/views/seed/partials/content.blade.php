{{-- Read-only concept note or full proposal. Expects $application and $stage. --}}
@php use App\Support\SeedGrant; @endphp

@if($stage === 'concept')
    <section class="card">
        <div class="card-body">
            <div class="form-grid">
                <div class="kv"><span class="k">Project title</span><span class="v">{{ $application->title ?: '—' }}</span></div>
                <div class="kv"><span class="k">Proposed mentor</span><span class="v">{{ $application->mentor_name ?: '—' }}</span>
                    <span class="small muted">{{ collect([$application->mentor_designation, $application->mentor_centre])->filter()->implode(' · ') }}</span></div>
            </div>
            @if($application->mentor_suitability)
                <div class="kv" style="margin-top:16px;"><span class="k">Why this mentor</span><p class="doc-text" style="font-size:14px;">{{ $application->mentor_suitability }}</p></div>
            @endif
        </div>
    </section>
@endif

<section class="card">
    <div class="card-body">
        @foreach(SeedGrant::sections($stage) as $key => [$heading])
            <div class="prose-section" id="{{ $stage }}-{{ $key }}">
                <h3>{{ $heading }}</h3>
                @php $text = $application->sectionText($stage, $key); @endphp
                @if($text !== '')
                    <p class="doc-text" style="margin-top:8px;">{{ $text }}</p>
                @else
                    <p class="muted" style="margin-top:8px;">Not written yet.</p>
                @endif
            </div>
        @endforeach
    </div>
</section>

<section class="card">
    <div class="card-head"><div><div class="card-title">{{ $stage === 'concept' ? 'Estimated budget' : 'Detailed budget' }}</div></div>
        <div class="mono" style="font-size:18px;">{{ SeedGrant::tzs($application->budgetTotal($stage)) }}</div></div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    @if($stage === 'proposal')<th>Category</th>@endif
                    <th>Item</th>
                    @if($stage === 'proposal')<th class="num">Q1</th><th class="num">Q2</th><th class="num">Q3</th><th class="num">Q4</th>@endif
                    <th class="num">Amount (TZS)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($application->budgetLines($stage) as $line)
                    <tr>
                        @if($stage === 'proposal')<td>{{ SeedGrant::BUDGET_CATEGORIES[$line->category] ?? '—' }}</td>@endif
                        <td>{{ $line->item }}</td>
                        @if($stage === 'proposal')
                            <td class="num">{{ number_format($line->q1) }}</td><td class="num">{{ number_format($line->q2) }}</td>
                            <td class="num">{{ number_format($line->q3) }}</td><td class="num">{{ number_format($line->q4) }}</td>
                        @endif
                        <td class="num strong">{{ number_format($line->total()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table-empty">No budget yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if($stage === 'proposal')
    <section class="card">
        <div class="card-head"><div class="card-title">Supporting documents</div></div>
        <div class="list">
            @forelse($application->documents as $doc)
                <a class="list-row" href="{{ route('seed.applications.documents.download', [$application, $doc]) }}">
                    <span class="pill pill-danger mono">{{ strtoupper(pathinfo($doc->file_name, PATHINFO_EXTENSION)) }}</span>
                    <span class="list-main"><span class="list-title">{{ $doc->kindLabel() }}</span><span class="list-sub">{{ $doc->file_name }}</span></span>
                    <i class="fas fa-download muted" aria-hidden="true"></i>
                </a>
            @empty
                <div class="table-empty">No documents uploaded.</div>
            @endforelse
        </div>
    </section>
@endif
