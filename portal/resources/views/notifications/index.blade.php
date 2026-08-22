@extends('adminlte::page')

@section('title', 'Notifications')

@section('content_header')
    <h1><i class="fas fa-bell mr-2"></i>Notifications</h1>
@endsection

@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $notifications->total() }} Notification(s)</h3>
        <div class="card-tools">
            <span class="badge badge-danger">
                {{ $notifications->where('read_at', null)->count() }} unread
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        @if($notifications->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fas fa-bell-slash fa-3x mb-3"></i>
                <h5>No notifications yet.</h5>
            </div>
        @else
        <ul class="list-group list-group-flush">
            @foreach($notifications as $notif)
            <li class="list-group-item {{ is_null($notif->read_at) ? 'bg-light' : '' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        @if(is_null($notif->read_at))
                            <span class="badge badge-primary mr-2">New</span>
                        @endif
                        <strong>{{ $notif->title }}</strong>
                        <p class="mb-1 text-muted">{{ $notif->message }}</p>
                        <small class="text-muted">{{ $notif->created_at->diffForHumans() }}</small>
                    </div>
                    @if(is_null($notif->read_at))
                    <form action="{{ route('notifications.read', $notif) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-xs btn-outline-secondary">
                            Mark read
                        </button>
                    </form>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </div>
    @if($notifications->hasPages())
    <div class="card-footer">{{ $notifications->links() }}</div>
    @endif
</div>

@endsection
