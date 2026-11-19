{{--
    Reviewer feedback as the applicant sees it: decided rounds only, reviewers
    by letter, never by name, and never the confidential note to GMU.
    Expects $application; optional $compact for the editor side panel.
--}}
@php
    $compact = $compact ?? false;
    $decided = $application->rounds->filter(fn ($r) => $r->decision !== null)->sortByDesc(fn ($r) => [$r->stage === 'proposal', $r->number]);
    $tones = ['accept' => 'ok', 'revise' => 'warn', 'reject' => 'danger'];
@endphp

@forelse($decided as $round)
    <section class="card" style="{{ $compact ? '' : 'margin-bottom:16px;' }}">
        <div class="card-head">
            <div>
                <div class="card-title">{{ ucfirst($round->stage === 'proposal' ? 'full proposal' : 'concept note') }} · round {{ $round->number }}</div>
                <div class="card-note">Decided {{ $round->decided_at?->format('j M Y') }}</div>
            </div>
            <span class="pill pill-{{ ['revise' => 'warn', 'advance' => 'ok', 'decline' => 'danger'][$round->decision] }}">
                {{ ['revise' => 'Revision requested', 'advance' => $round->stage === 'proposal' ? 'Recommended' : 'Shortlisted', 'decline' => 'Not taken forward'][$round->decision] }}
            </span>
        </div>
        <div class="card-body stack-sm">
            @if($round->decision_note)
                <div class="banner banner-info" style="padding:14px 16px;">
                    <div><strong>From GMU</strong><p style="margin-top:4px; font-size:14px;">{{ $round->decision_note }}</p></div>
                </div>
            @endif
            @foreach($round->reviews->filter->isSubmitted()->sortBy(fn ($r) => $r->assignment->label) as $review)
                <div style="padding:14px 0; border-top:1px solid var(--line-soft);">
                    <div class="row-between">
                        <strong>Reviewer {{ $review->assignment->label }}</strong>
                        <span class="pill pill-{{ $tones[$review->recommendation] ?? '' }}">{{ \App\Models\Seed\SeedReview::RECOMMENDATIONS[$review->recommendation] ?? '' }}</span>
                    </div>
                    <p class="doc-text" style="margin-top:8px; font-size:14px;">{{ $review->comments }}</p>
                </div>
            @endforeach
            @if($round->response)
                <div style="padding:14px 0 0; border-top:1px solid var(--line-soft);">
                    <strong>Your response</strong> <span class="small muted">· {{ $round->responded_at?->format('j M Y') }}</span>
                    <p class="doc-text" style="margin-top:6px; font-size:14px;">{{ $round->response }}</p>
                </div>
            @endif
        </div>
    </section>
@empty
    @unless($compact)
        <div class="card"><div class="empty"><p>No reviewer feedback yet.</p></div></div>
    @endunless
@endforelse
