@extends('layouts.app')
@section('title', auth()->user()->hasRole('doctor') ? 'My appointments' : 'Appointments')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title mb-0">{{ auth()->user()->hasRole('doctor') ? 'My appointments' : 'Appointments' }}</h1>
        <div class="d-flex gap-2">
            @can('appointments.view')<a href="{{ route('appointments.schedule') }}" class="btn btn-outline-secondary"><i class="bi bi-grid-3x3-gap me-1"></i>Daily schedule</a>@endcan
            @can('appointments.book')<a href="{{ route('appointments.book') }}" class="btn btn-teal"><i class="bi bi-calendar-plus me-1"></i>Book appointment</a>@endcan
        </div>
    </div>

    <form method="GET" class="card card-body mb-3 row g-2 align-items-end flex-row">
        <div class="col-md-2"><label class="form-label" for="date">Date</label>
            <input type="date" id="date" name="date" value="{{ request('date') }}" class="form-control"></div>
        @unless(auth()->user()->hasRole('doctor'))
            <div class="col-md-3"><label class="form-label" for="doctor_id">Doctor</label>
                <select name="doctor_id" id="doctor_id" class="form-select"><option value="">All doctors</option>
                    @foreach($doctors as $d)<option value="{{ $d->id }}" @selected(request('doctor_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
        @endunless
        <div class="col-md-3"><label class="form-label" for="q">Patient</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" class="form-control" placeholder="Number, name or phone"></div>
        <div class="col-md-2"><label class="form-label" for="status">Status</label>
            <select name="status" id="status" class="form-select"><option value="">All</option>
                @foreach(\App\Models\Appointment::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></div>
        @include('partials.filter-actions', ['clear' => route('appointments.index')])
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Appointment</th><th>Date &amp; time</th><th>Patient</th><th>Doctor</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse($appointments as $a)
                    <tr>
                        <td class="fw-semibold">{{ $a->appointment_number }}</td>
                        <td>{{ $a->appointment_date->format('d M Y') }} · {{ $a->time_short }}</td>
                        <td>{{ $a->patient->full_name }}<div class="small text-muted">{{ $a->patient->patient_number }}</div></td>
                        <td>{{ $a->doctor->name }}</td>
                        <td><x-status-badge :status="$a->status" /></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('appointments.show', $a) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @if(auth()->user()->hasRole('doctor') && $a->status === 'checked_in')
                                <a href="{{ route('consultations.create', $a) }}" class="btn btn-sm btn-teal">Consult</a>
                            @endif
                            @can('appointments.cancel')
                                @if(in_array($a->status, ['scheduled', 'checked_in']))
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal"
                                            data-action="{{ route('appointments.cancel', $a) }}" data-label="{{ $a->appointment_number }} – {{ $a->patient->full_name }}, {{ $a->appointment_date->format('d M Y') }} {{ $a->time_short }}">Cancel</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><i class="bi bi-calendar2"></i>No appointments match your filters.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($appointments->hasPages())<div class="card-footer">{{ $appointments->links() }}</div>@endif
    </div>
    @can('appointments.cancel')@include('partials.cancel-modal')@endcan
@endsection
