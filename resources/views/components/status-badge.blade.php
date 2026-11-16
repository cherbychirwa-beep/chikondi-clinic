@props(['status'])
@php
    $map = [
        'scheduled' => ['primary', 'Scheduled'],
        'checked_in' => ['info', 'Checked-in'],
        'completed' => ['success', 'Completed'],
        'cancelled' => ['danger', 'Cancelled'],
        'no_show' => ['warning', 'No-show'],
        'partial' => ['warning', 'Partial'],
        'paid' => ['success', 'Paid in full'],
        'active' => ['success', 'Active'],
        'inactive' => ['secondary', 'Inactive'],
    ];
    [$color, $text] = $map[$status] ?? ['secondary', ucfirst(str_replace('_', ' ', $status))];
@endphp
<span class="badge text-bg-{{ $color }}">{{ $text }}</span>
