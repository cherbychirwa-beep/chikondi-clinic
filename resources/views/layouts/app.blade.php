<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('chikondi.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @include('partials.styles')
</head>
<body>
@php($menu = auth()->user()->staff()->menu())
<div class="app-shell">
    <aside class="sidebar no-print" id="sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-mark"><i class="bi bi-heart-pulse-fill"></i></span>
            <span><strong>{{ config('chikondi.name') }}</strong><small>{{ config('chikondi.tagline') }}</small></span>
        </a>
        <nav class="nav flex-column" aria-label="Main navigation">
            @foreach($menu as $item)
                @can($item['can'])
                    <a class="nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                        <i class="bi {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
                    </a>
                @endcan
            @endforeach
        </nav>
        <div class="sidebar-footer">
            <i class="bi bi-person-circle"></i> {{ auth()->user()->staff()->title() }}
        </div>
    </aside>

    <div class="main">
        <header class="topbar no-print">
            <button class="btn btn-light btn-sm d-lg-none" type="button" id="sidebarToggle" aria-label="Toggle navigation"><i class="bi bi-list fs-5"></i></button>
            <div class="topbar-date d-none d-md-block text-muted small"><i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, d F Y') }}</div>
            <div class="ms-auto d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-light btn-sm position-relative" data-bs-toggle="dropdown" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        @if(count($notifications))<span class="badge rounded-pill bg-danger notif-badge">{{ count($notifications) }}</span>@endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:280px">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        @forelse($notifications as $n)
                            <li><a class="dropdown-item small" href="{{ $n['route'] }}">{{ $n['text'] }}</a></li>
                        @empty
                            <li><span class="dropdown-item-text small text-muted">You're all caught up.</span></li>
                        @endforelse
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                        <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->username }} · {{ auth()->user()->role->name }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('password.change') }}"><i class="bi bi-key me-2"></i>Change password</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" data-no-loading>
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="content">
            <nav aria-label="breadcrumb" class="no-print">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    @yield('crumbs')
                    <li class="breadcrumb-item active" aria-current="page">@yield('title', 'Dashboard')</li>
                </ol>
            </nav>

            @include('partials.flash')

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => document.body.classList.toggle('sidebar-open'));

    // Confirmation dialogs (data-confirm) and loading state on submit buttons.
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', e => {
            const message = form.dataset.confirm;
            if (message && !confirm(message)) { e.preventDefault(); return; }
            if (form.hasAttribute('data-no-loading')) return;
            const btn = e.submitter || form.querySelector('[type=submit]');
            if (btn && btn.type === 'submit') {
                setTimeout(() => {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Please wait…';
                }, 0);
            }
        });
    });
    window.addEventListener('pageshow', e => { if (e.persisted) location.reload(); });
</script>
@stack('scripts')
</body>
</html>
