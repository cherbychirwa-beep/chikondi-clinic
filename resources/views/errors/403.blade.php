<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied · {{ config('chikondi.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
<div class="text-center p-4">
    <i class="bi bi-shield-lock display-3 text-danger"></i>
    <h1 class="h3 mt-3">Access denied</h1>
    <p class="text-muted">You do not have permission to view this page or record.</p>
    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary">Back to safety</a>
</div>
</body>
</html>
