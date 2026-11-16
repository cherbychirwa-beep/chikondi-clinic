<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page expired · {{ config('chikondi.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
<div class="text-center p-4">
    <i class="bi bi-hourglass-split display-3 text-warning"></i>
    <h1 class="h3 mt-3">Page expired</h1>
    <p class="text-muted">Your session or form expired. Please go back, refresh the page and try again.</p>
    <a href="{{ url('/') }}" class="btn btn-primary">Continue</a>
</div>
</body>
</html>
