@extends('adminlte::page')

@section('title', $user->name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-user mr-2"></i>{{ $user->name }}</h1>
        <div>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-default ml-1">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Profile</h3>
                <div class="card-tools">
                    @if($user->is_active)
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-danger">Inactive</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr><th width="160" class="text-muted">Name</th><td><strong>{{ $user->name }}</strong></td></tr>
                    <tr><th class="text-muted">Email</th><td>{{ $user->email }}</td></tr>
                    <tr><th class="text-muted">Role</th>
                        <td>
                            @if($user->role)
                                <span class="badge badge-secondary">{{ $user->role->display_name }}</span>
                            @else
                                <span class="text-muted">No role</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th class="text-muted">Phone</th><td>{{ $user->phone ?? '-' }}</td></tr>
                    <tr><th class="text-muted">Department</th><td>{{ $user->department ?? '-' }}</td></tr>
                    <tr><th class="text-muted">Research Center</th><td>{{ $user->research_center ?? '-' }}</td></tr>
                    <tr><th class="text-muted">Joined</th><td>{{ $user->created_at->format('d M Y') }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Quick Actions</h3></div>
            <div class="card-body">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-block mb-2">
                    <i class="fas fa-edit mr-1"></i>Edit Profile & Role
                </a>
                @if($user->id !== auth()->id())
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                      onsubmit="return confirm('Deactivate this user?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-{{ $user->is_active ? 'outline-danger' : 'outline-success' }} btn-block">
                        <i class="fas fa-{{ $user->is_active ? 'ban' : 'check' }} mr-1"></i>
                        {{ $user->is_active ? 'Deactivate Account' : 'Account is Inactive' }}
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
