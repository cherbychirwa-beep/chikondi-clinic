@extends('layouts.guest')
@section('title', 'Sign in')
@section('content')
    <h2 class="h5 mb-3">Staff sign in</h2>
    <form method="POST" action="{{ route('login.attempt') }}" class="row g-3" novalidate>
        @csrf
        <x-field name="username" label="Username" :required="true" col="col-12" autocomplete="username" autofocus maxlength="50" />
        <x-field name="password" label="Password" type="password" :required="true" col="col-12" autocomplete="current-password" />
        <div class="col-12 form-check ms-1">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
            <label class="form-check-label small" for="remember">Keep me signed in on this computer</label>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1"></i>Sign in</button>
        </div>
    </form>
@endsection
