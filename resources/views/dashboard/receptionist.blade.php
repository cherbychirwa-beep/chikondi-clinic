@extends('layouts.app')
@section('title', 'Reception Dashboard')
@section('content')
    <h1 class="page-title mb-3">Reception desk</h1>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-4"><x-stat-card label="Today's appointments" :value="$stats['appointments_today']" icon="bi-calendar-check" color="primary" /></div>
        <div class="col-6 col-xl-4"><x-stat-card label="Upcoming appointments" :value="$stats['upcoming']" icon="bi-calendar-event" color="info" /></div>
        <div class="col-12 col-xl-4"><x-stat-card label="Cancelled today" :value="$stats['cancelled_today']" icon="bi-calendar-x" color="danger" /></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card mb-3"><div class="card-header">Quick patient search</div>
                <div class="card-body">
                    <form method="GET" action="{{ route('patients.index') }}" class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Patient number, name or phone" required>
                        <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                    </form>
                </div></div>
            <div class="card"><div class="card-header">Quick appointment booking</div>
                <div class="card-body">
                    <p class="small text-muted">Find the patient by number, name or date of birth (YYYY-MM-DD), then pick a slot.</p>
                    <form method="GET" action="{{ route('appointments.book') }}" class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Patient no. or date of birth" required>
                        <button class="btn btn-teal"><i class="bi bi-calendar-plus me-1"></i>Book</button>
                    </form>
                </div></div>
        </div>
        <div class="col-lg-7">
            <div class="card"><div class="card-header">Next scheduled appointments</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>When</th><th>Patient</th><th>Doctor</th><th></th></tr></thead>
                        <tbody>
                        @forelse($stats['next_appointments'] as $a)
                            <tr>
                                <td>{{ $a->appointment_date->format('d M') }} · {{ $a->time_short }}</td>
                                <td>{{ $a->patient->full_name }}<div class="small text-muted">{{ $a->patient->patient_number }}</div></td>
                                <td>{{ $a->doctor->name }}</td>
                                <td class="text-end"><a href="{{ route('appointments.show', $a) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="empty-state"><i class="bi bi-calendar2"></i>No upcoming appointments.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div></div>
        </div>
    </div>
@endsection
