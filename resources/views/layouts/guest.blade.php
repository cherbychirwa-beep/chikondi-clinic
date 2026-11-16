<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') · {{ config('chikondi.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @include('partials.styles')
    <style>
        body { background: linear-gradient(135deg, #0b2545 0%, #13315c 55%, #12a594 140%); min-height: 100vh; }
        .auth-card { max-width: 430px; width: 100%; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
<div class="auth-card">
    <div class="text-center text-white mb-4">
        <span class="brand-mark mx-auto mb-2" style="width:56px;height:56px;font-size:1.8rem"><i class="bi bi-heart-pulse-fill"></i></span>
        <h1 class="h3 mb-0">{{ config('chikondi.name') }}</h1>
        <div class="small opacity-75">{{ config('chikondi.tagline') }} Management System</div>
    </div>
    <div class="card shadow">
        <div class="card-body p-4">
            @include('partials.flash')
            @yield('content')
        </div>
    </div>
    <p class="text-center text-white-50 small mt-3 mb-0">Authorised staff only. All activity is audited.</p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('form').forEach(form => form.addEventListener('submit', e => {
        const btn = e.submitter || form.querySelector('[type=submit]');
        if (btn) setTimeout(() => { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Please wait…'; }, 0);
    }));
    window.addEventListener('pageshow', e => { if (e.persisted) location.reload(); });
</script>
</body>
</html>
