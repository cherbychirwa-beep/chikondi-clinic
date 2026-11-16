@extends('layouts.app')
@section('title', 'Attendant Dashboard')
@section('content')
    <h1 class="page-title mb-3">Front desk</h1>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3"><x-stat-card label="Today's appointments" :value="$stats['appointments_today']" icon="bi-calendar-check" color="primary" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Awaiting check-in" :value="$stats['awaiting']" icon="bi-hourglass" color="warning" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Checked in" :value="$stats['checked_in']" icon="bi-clipboard-check" color="success" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Pending payments" :value="$stats['pending_payments']" icon="bi-cash-coin" color="danger" /></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card"><div class="card-header">Quick actions</div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('patients.create') }}" class="btn btn-teal"><i class="bi bi-person-plus me-1"></i>Register new patient</a>
                    <a href="{{ route('checkin.index') }}" class="btn btn-primary"><i class="bi bi-clipboard-check me-1"></i>Check-in desk</a>
                    <a href="{{ route('payments.pending') }}" class="btn btn-outline-primary"><i class="bi bi-receipt me-1"></i>Record a payment</a>
                </div></div>
        </div>
        <div class="col-lg-8">
            <div class="card"><div class="card-header">Patients awaiting check-in</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th></tr></thead>
                        <tbody>
                        @forelse($stats['awaiting_list'] as $a)
                            <tr><td>{{ $a->time_short }}</td>
                                <td>{{ $a->patient->full_name }}<div class="small text-muted">{{ $a->patient->patient_number }}</div></td>
                                <td>{{ $a->doctor->name }}</td></tr>
                        @empty
                            <tr><td colspan="3"><div class="empty-state"><i class="bi bi-check2-circle"></i>Nobody is waiting to be checked in.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div></div>
        </div>
    </div>
@endsection
