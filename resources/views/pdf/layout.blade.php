<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2a37; }
        .head { border-bottom: 2px solid #12a594; padding-bottom: 8px; margin-bottom: 14px; }
        .head h1 { margin: 0; font-size: 20px; color: #0b2545; }
        .head .sub { color: #64748b; font-size: 10px; }
        h2 { font-size: 13px; color: #0b2545; margin: 16px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0b2545; color: #fff; text-align: left; padding: 5px 6px; font-size: 10px; }
        td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .right { text-align: right; }
        .muted { color: #64748b; }
        .box { border: 1px solid #e2e8f0; padding: 8px; margin-bottom: 8px; }
        .foot { margin-top: 18px; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 6px; }
        .kv td { border: 0; padding: 3px 0; }
    </style>
</head>
<body>
<div class="head">
    <h1>{{ config('chikondi.name') }}</h1>
    <div class="sub">{{ config('chikondi.tagline') }} · {{ config('chikondi.address') }} · {{ config('chikondi.phone') }}</div>
</div>
@yield('body')
<div class="foot">Generated {{ now()->format('d M Y H:i') }} by {{ auth()->user()?->name }} · Confidential – contains personal and health information.</div>
</body>
</html>
