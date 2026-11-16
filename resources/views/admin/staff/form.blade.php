@extends('layouts.app')
@section('title', $staff ? 'Edit staff account' : 'New staff account')
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff</a></li>@endsection
@section('content')
    <div class="card" style="max-width:820px">
        <div class="card-body p-4">
            <form method="POST" action="{{ $staff ? route('staff.update', $staff) : route('staff.store') }}" class="row g-3">
                @csrf
                @if($staff) @method('PUT') @endif

                <x-field name="name" label="Full name" :value="$staff?->name" :required="true" maxlength="120" />
                <x-field name="email" label="Email (optional)" type="email" :value="$staff?->email" maxlength="255" />

                @unless($staff)
                    <x-field name="username" label="Username" :required="true" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" help="Letters, numbers, dot, dash, underscore." />
                    <x-field name="password" label="Temporary password" type="password" :required="true" minlength="8" autocomplete="new-password"
                             help="The user must change this at first login." />
                @else
                    <div class="col-md-6"><label class="form-label">Username</label><input class="form-control" value="{{ $staff->username }}" disabled></div>
                @endunless

                <x-select name="role_id" label="Role" :required="true" :value="$staff?->role_id" :options="$roles->pluck('name', 'id')->all()" placeholder="Select role…" />

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $staff ? 'Save changes' : 'Create account' }}</button>
                    <a href="{{ route('staff.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
