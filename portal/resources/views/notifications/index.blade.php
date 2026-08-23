@extends('layouts.gmu')

@section('title', 'Notifications')
@section('crumb', 'Account')

@section('content')
@php
    $icons = [
        'new_call' => 'fa-bullhorn', 'selection_result' => 'fa-user-check', 'review_assigned' => 'fa-clipboard-check',
        'proposal_status' => 'fa-file-alt', 'proposal_cleared' => 'fa-stamp', 'award_created' => 'fa-trophy',
        'award_amended' => 'fa-file-signature', 'award_closed' => 'fa-flag-checkered', 'report_due' => 'fa-file-invoice',
        'report_decision' => 'fa-check-double', 'compliance_due' => 'fa-calendar-check',
        'compliance_overdue' => 'fa-exclamation-triangle', 'ethics_renewal' => 'fa-shield-alt',
        'team_added' => 'fa-users', 'sub_award_created' => 'fa-handshake',
    ];
    $unread = $notifications->whereNull('read_at')->count();
@endphp

<x-ui.page-head
    title="Notifications"
    :lede="$unread > 0 ? $unread . ' unread on this page' : 'Everything here has been read'" />

<x-ui.card :flush="true">
    <div class="list">
        @forelse($notifications as $notification)
            <div class="list-row" style="align-items:flex-start; background: {{ $notification->read_at ? 'transparent' : 'var(--info-bg)' }};">
                <span style="width:34px; height:34px; border-radius:9px; flex:0 0 34px; display:flex; align-items:center;
                             justify-content:center; background:var(--line-soft); color:var(--navy-500);">
                    <i class="fas {{ $icons[$notification->type] ?? 'fa-bell' }}"></i>
                </span>

                <div class="list-main">
                    <span class="list-title">{{ $notification->title }}</span>
                    <span class="list-sub" style="color:var(--muted); line-height:1.6;">{{ $notification->message }}</span>
                    <span class="tiny muted-3" style="margin-top:4px;">{{ $notification->created_at?->diffForHumans() }}</span>
                </div>

                @unless($notification->read_at)
                    <form method="POST" action="{{ route('notifications.read', $notification) }}" class="inline-form">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm">Mark read</button>
                    </form>
                @endunless
            </div>
        @empty
            <x-ui.empty icon="fa-bell" title="Nothing to read">
                Notifications arrive when a call opens, a review is waiting on you, a report falls due, an award is
                recorded, or a deadline slips.
            </x-ui.empty>
        @endforelse
    </div>
</x-ui.card>

<div class="pagination-wrap">{{ $notifications->links() }}</div>
@endsection
