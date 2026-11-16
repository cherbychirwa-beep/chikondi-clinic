@props(['label', 'value', 'icon' => 'bi-activity', 'color' => 'primary', 'sub' => null])
<div class="card stat-card h-100">
    <div class="card-body d-flex align-items-center gap-3">
        <div class="icon bg-{{ $color }}-subtle text-{{ $color }}"><i class="bi {{ $icon }}"></i></div>
        <div>
            <div class="text-muted small">{{ $label }}</div>
            <div class="value">{{ $value }}</div>
            @if($sub)<div class="small text-muted">{{ $sub }}</div>@endif
        </div>
    </div>
</div>
