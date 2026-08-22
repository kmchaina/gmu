@extends('adminlte::page')

@section('title', 'Edit User')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-user-edit mr-2"></i>Edit User</h1>
        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('admin.users.update', $user) }}" method="POST">
@csrf
@method('PUT')

<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Account Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="form-control @error('name') is-invalid @enderror">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role_id" class="form-control @error('role_id') is-invalid @enderror">
                                <option value="">-- No role --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                        {{ $role->display_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>New Password <small class="text-muted">(leave blank to keep current)</small></label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror" placeholder="Min 8 characters">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                                   class="form-control" placeholder="+255...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" value="{{ old('department', $user->department) }}"
                                   class="form-control" placeholder="e.g. Epidemiology">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Research Center</label>
                            <input type="text" name="research_center" value="{{ old('research_center', $user->research_center) }}"
                                   class="form-control" placeholder="e.g. HQ, Tanga">
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <div class="col-md-4">

        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Account Settings</h3></div>
            <div class="card-body">
                <div class="custom-control custom-switch">
                    <input type="checkbox" name="is_active" value="1" class="custom-control-input"
                           id="isActive" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="isActive">Account Active</label>
                </div>
                <small class="text-muted d-block mt-1">Inactive users cannot log in to the system.</small>

                @if($user->id === auth()->id())
                <div class="alert alert-warning mt-3 mb-0 small">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    You are editing your own account. Be careful with role and status changes.
                </div>
                @endif
            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body">
                <p class="small text-muted mb-1">
                    <i class="fas fa-clock mr-1"></i>Account created {{ $user->created_at->format('d M Y') }}
                </p>
                <p class="small text-muted mb-0">
                    <i class="fas fa-sign-in-alt mr-1"></i>Last updated {{ $user->updated_at->diffForHumans() }}
                </p>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-1"></i> Save Changes
        </button>
        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-default btn-block">Cancel</a>

    </div>
</div>

</form>

@endsection
