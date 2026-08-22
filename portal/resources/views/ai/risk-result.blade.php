@extends('adminlte::page')

@section('title', 'Risk Analysis — ' . $award->title)

@section('content_header')
    <h1><i class="fas fa-shield-alt mr-2"></i>Risk Analysis: {{ $award->title }}</h1>
@endsection

@section('content')

@php
    $overallColor = match($result['overall_risk']) {
        'high'   => 'danger',
        'medium' => 'warning',
        'low'    => 'info',
        default  => 'success',
    };
@endphp

<div class="row mb-3">
    <div class="col-md-4">
        <div class="info-box bg-{{ $overallColor }}">
            <span class="info-box-icon"><i class="fas fa-{{ $result['overall_risk'] === 'none' ? 'check' : 'exclamation-triangle' }}"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Overall Risk</span>
                <span class="info-box-number">{{ ucfirst($result['overall_risk']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-light">
            <span class="info-box-icon"><i class="fas fa-robot text-muted"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted">AI Provider</span>
                <span class="info-box-number text-muted" style="font-size:.95rem">{{ $result['provider'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-light">
            <span class="info-box-icon"><i class="fas fa-flag text-muted"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted">Flags Found</span>
                <span class="info-box-number text-muted">{{ count($result['flags']) }}</span>
            </div>
        </div>
    </div>
</div>

@if($result['ai_narrative'])
<div class="alert alert-light border-left border-primary border-3 mb-3">
    <strong><i class="fas fa-robot mr-1 text-primary"></i>AI Assessment</strong><br>
    {{ $result['ai_narrative'] }}
</div>
@endif

@if(empty($result['flags']))
<div class="alert alert-success">
    <i class="fas fa-check-circle mr-2"></i>No compliance issues detected for this award. Keep up the good work!
</div>
@else
<div class="card card-outline card-{{ $overallColor }}">
    <div class="card-header"><h3 class="card-title">Risk Flags</h3></div>
    <div class="card-body p-0">
        @foreach($result['flags'] as $flag)
        @php $c = match($flag['level']) { 'high'=>'danger','medium'=>'warning',default=>'info' }; @endphp
        <div class="d-flex align-items-start px-3 py-3 border-bottom">
            <span class="badge badge-{{ $c }} mr-3 mt-1" style="min-width:60px;text-align:center">
                {{ ucfirst($flag['level']) }}
            </span>
            <div>
                <strong class="text-{{ $c }}">{{ $flag['category'] }}</strong><br>
                <span>{{ $flag['message'] }}</span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="mt-3">
    <a href="{{ route('awards.show', $award) }}" class="btn btn-primary">
        <i class="fas fa-trophy mr-1"></i>View Award
    </a>
    <a href="{{ route('ai.risk') }}" class="btn btn-secondary ml-2">
        <i class="fas fa-arrow-left mr-1"></i>Back
    </a>
</div>
@endsection
