<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Not found · {{ config('chikondi.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
<div class="text-center p-4">
    <i class="bi bi-search display-3 text-secondary"></i>
    <h1 class="h3 mt-3">Page not found</h1>
    <p class="text-muted">The page or record you requested does not exist.</p>
    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary">Go to dashboard</a>
</div>
</body>
</html>
