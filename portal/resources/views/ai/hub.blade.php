@extends('layouts.gmu')

@section('title', 'AI intelligence hub')
@section('crumb', 'Intelligence')

@section('content')
<x-ui.page-head
    title="AI intelligence hub"
    lede="Scoring and risk analysis run on rules the unit can inspect. Written summaries are added when a language model is configured — never the other way round.">
    <x-slot:actions>
        <x-ui.pill :tone="$llmActive ? 'ok' : null">
            <i class="fas {{ $llmActive ? 'fa-circle-check' : 'fa-circle-minus' }}"></i>
            {{ $llmActive ? 'Language model active' : 'Rule-based only' }}
        </x-ui.pill>
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">

    @unless($llmActive)
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>No language model is configured.</strong>
                Matching scores and risk flags below still work in full — they are computed from your own data by rules
                in the codebase. Setting <span class="mono">ANTHROPIC_API_KEY</span> adds a written summary on top;
                nothing depends on it.
            </div>
        </div>
    @endunless

    <div class="grid grid-2">
        <x-ui.card title="Grant matching" note="Which researchers fit a call, and why">
            <p class="small muted" style="line-height:1.7;">
                Every active researcher is scored against a call out of 100: expertise against the theme,
                funded track record, relevant qualifications, publications, budget fit, and whether any
                conflict-of-interest disclosure is outstanding. The breakdown is shown per researcher, so the
                Coordinator can see the reasoning rather than a number.
            </p>
            <div class="form-actions" style="margin-top:16px;">
                <a href="{{ route('ai.matching') }}" class="btn btn-primary">Match researchers to a call</a>
            </div>
        </x-ui.card>

        <x-ui.card title="Compliance risk analysis" note="What is going wrong on an award, ranked">
            <p class="small muted" style="line-height:1.7;">
                Reads an award end to end — overdue and upcoming reports, compliance deliverables, ethics expiry,
                burn rate against the period remaining, and the closeout window — and returns flags ranked high,
                medium and low, each naming the record it came from.
            </p>
            <div class="form-actions" style="margin-top:16px;">
                <a href="{{ route('ai.risk') }}" class="btn btn-primary">Analyse an award</a>
            </div>
        </x-ui.card>
    </div>

    <div class="grid grid-2">
        <x-ui.card title="Report quality check" note="Available from a report page">
            <p class="small muted" style="line-height:1.7;">
                Checks a draft narrative before it goes to the donor: length, whether it contains quantitative
                results, and whether it covers objectives, results, challenges and next steps. With a model
                configured it adds three specific suggestions.
            </p>
        </x-ui.card>

        <x-ui.card title="Proposal drafting" note="Available while writing a proposal">
            <p class="small muted" style="line-height:1.7;">
                Drafts an abstract, background, objectives, methodology or impact section from the context you give
                it, as a starting point for the PI to work from. This one needs a configured model — there is no
                rule-based equivalent.
            </p>
            @unless($llmActive)
                <p class="small text-warn" style="margin-top:12px;">Unavailable until a model is configured.</p>
            @endunless
        </x-ui.card>
    </div>

    <x-ui.card title="How this is wired" note="For whoever maintains the system">
        <div class="stack-sm small muted" style="line-height:1.7;">
            <div class="row-between">
                <span>Provider</span>
                <span class="mono">{{ $providerName }}</span>
            </div>
            <div class="row-between">
                <span>Behaviour without a key</span>
                <span>Rule-based engines run; written summaries are skipped</span>
            </div>
            <div class="row-between">
                <span>Rate limit</span>
                <span>20 requests per user per minute</span>
            </div>
            <div class="row-between">
                <span>Data sent</span>
                <span>Only the text of the record being analysed</span>
            </div>
        </div>
    </x-ui.card>
</div>
@endsection
