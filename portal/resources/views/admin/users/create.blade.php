@extends('adminlte::page')

@section('title', 'Add User')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-user-plus mr-2"></i>Add User</h1>
        <a href="{{ route('admin.users.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('admin.users.store') }}" method="POST">
@csrf

<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Account Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror" placeholder="Dr. John Doe">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   class="form-control @error('email') is-invalid @enderror" placeholder="user@nimr.or.tz">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role_id" class="form-control @error('role_id') is-invalid @enderror">
                                <option value="">-- No role assigned --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
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
                            <label>Password <span class="text-danger">*</span></label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror" placeholder="Min 8 characters">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="+255...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" value="{{ old('department') }}"
                                   class="form-control" placeholder="e.g. Epidemiology">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Research Center</label>
                            <input type="text" name="research_center" value="{{ old('research_center') }}"
                                   class="form-control" placeholder="e.g. HQ, Tanga, Mwanza">
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
                           id="isActive" {{ old('is_active', '1') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="isActive">Account Active</label>
                </div>
                <small class="text-muted d-block mt-1">Inactive users cannot log in.</small>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-user-plus mr-1"></i> Create Account
        </button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-default btn-block">Cancel</a>

    </div>
</div>

</form>

@endsection
