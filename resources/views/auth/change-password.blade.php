@php($forced = auth()->user()->must_change_password)
@extends($forced ? 'layouts.guest' : 'layouts.app')
@section('title', 'Change password')
@section('content')
    <div @class(['row justify-content-center' => ! $forced])>
        <div @class(['col-lg-6' => ! $forced])>
            @unless($forced)<div class="card"><div class="card-body p-4">@endunless
            <h2 class="h5 mb-1">{{ $forced ? 'Set a new password' : 'Change your password' }}</h2>
            @if($forced)<p class="text-muted small">For security you must replace your temporary password before continuing.</p>@endif
            <form method="POST" action="{{ route('password.change.update') }}" class="row g-3 mt-1">
                @csrf
                <x-field name="current_password" label="Current password" type="password" :required="true" col="col-12" autocomplete="current-password" />
                <x-field name="password" label="New password" type="password" :required="true" col="col-12" autocomplete="new-password" minlength="8"
                         help="At least 8 characters, including letters and numbers." />
                <x-field name="password_confirmation" label="Confirm new password" type="password" :required="true" col="col-12" autocomplete="new-password" minlength="8" />
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update password</button>
                    @unless($forced)<a href="{{ route('dashboard') }}" class="btn btn-light">Cancel</a>@endunless
                </div>
            </form>
            @if($forced)
                <form method="POST" action="{{ route('logout') }}" class="mt-3" data-no-loading>@csrf
                    <button class="btn btn-link btn-sm p-0">Sign out instead</button>
                </form>
            @endif
            @unless($forced)</div></div>@endunless
        </div>
    </div>
@endsection
