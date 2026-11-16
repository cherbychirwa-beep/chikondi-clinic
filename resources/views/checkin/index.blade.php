@extends('layouts.app')
@section('title', 'Patient check-in')
@section('content')
    <h1 class="page-title mb-3">Patient check-in <span class="text-muted fs-6">· {{ today()->format('l, d F Y') }}</span></h1>

    <form method="GET" class="card card-body mb-3 row g-2 align-items-end flex-row">
        <div class="col-md-4"><label class="form-label" for="q">Patient number</label>
            <input type="text" id="q" name="q" value="{{ $term }}" class="form-control" placeholder="e.g. CC-000012" autofocus></div>
        <div class="col-md-3"><label class="form-label" for="status">Status</label>
            <select name="status" id="status" class="form-select"><option value="">All</option>
                @foreach(\App\Models\Appointment::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-auto d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Find appointment</button>
            <a href="{{ route('checkin.index') }}" class="btn btn-outline-secondary">Clear filters</a>
        </div>
    </form>

    @if($notFound)
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>No appointment found today for patient number <strong>{{ $term }}</strong>. Check the number, or ask reception to book one.</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Time</th><th>Appointment</th><th>Patient (verify)</th><th>Doctor / room</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                @forelse($appointments as $a)
                    <tr>
                        <td class="fw-semibold">{{ $a->time_short }}</td>
                        <td>{{ $a->appointment_number }}</td>
                        <td>{{ $a->patient->full_name }}
                            <div class="small text-muted">{{ $a->patient->patient_number }} · DOB {{ $a->patient->date_of_birth->format('d M Y') }} · {{ $a->patient->phone }}</div></td>
                        <td>{{ $a->doctor->name }}<div class="small text-muted">Room {{ $a->doctor->consulting_room }}</div></td>
                        <td><x-status-badge :status="$a->status" />
                            @if($a->checked_in_at)<div class="small text-muted">{{ $a->checked_in_at->format('H:i') }}</div>@endif</td>
                        <td class="text-end text-nowrap">
                            @if($a->status === 'scheduled')
                                <form method="POST" action="{{ route('checkin.store', $a) }}" class="d-inline" data-confirm="Confirm identity and check in {{ $a->patient->full_name }}?">
                                    @csrf <button class="btn btn-sm btn-teal"><i class="bi bi-check2-circle me-1"></i>Check in</button></form>
                                <form method="POST" action="{{ route('checkin.noshow', $a) }}" class="d-inline" data-confirm="Mark {{ $a->patient->full_name }} as a no-show?">
                                    @csrf <button class="btn btn-sm btn-outline-secondary">No-show</button></form>
                            @elseif($a->status === 'cancelled')
                                <span class="text-danger small">Cannot check in – cancelled</span>
                            @elseif($a->status === 'checked_in')
                                <span class="text-success small">Waiting for doctor</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><i class="bi bi-calendar2"></i>No appointments for today.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
