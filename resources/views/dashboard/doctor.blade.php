@extends('layouts.app')
@section('title', 'Doctor Dashboard')
@section('content')
    <h1 class="page-title mb-3">Good day, {{ auth()->user()->name }}</h1>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3"><x-stat-card label="Today's appointments" :value="$stats['appointments_today']" icon="bi-calendar-check" color="primary" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Waiting patients" :value="$stats['waiting']" icon="bi-people" color="warning" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Consultations completed today" :value="$stats['completed_today']" icon="bi-clipboard2-pulse" color="success" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Upcoming appointments" :value="$stats['upcoming']" icon="bi-calendar-event" color="info" /></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card"><div class="card-header d-flex justify-content-between">Waiting for consultation
                    <a href="{{ route('consultations.queue') }}" class="small">Open queue</a></div>
                <ul class="list-group list-group-flush">
                    @forelse($stats['waiting_list'] as $a)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>{{ $a->patient->full_name }}<div class="small text-muted">{{ $a->patient->patient_number }} · booked {{ $a->time_short }}</div></div>
                            <a href="{{ route('consultations.create', $a) }}" class="btn btn-sm btn-teal">Start</a>
                        </li>
                    @empty
                        <li class="list-group-item"><div class="empty-state"><i class="bi bi-person-check"></i>No patients waiting.</div></li>
                    @endforelse
                </ul></div>
        </div>
        <div class="col-lg-6">
            <div class="card"><div class="card-header">Recent patients</div>
                <ul class="list-group list-group-flush">
                    @forelse($stats['recent_patients'] as $c)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>{{ $c->patient->full_name }}<div class="small text-muted">{{ $c->consultation_date->format('d M Y') }} · {{ \Illuminate\Support\Str::limit($c->diagnosis, 50) }}</div></div>
                            <a href="{{ route('consultations.show', $c) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        </li>
                    @empty
                        <li class="list-group-item"><div class="empty-state"><i class="bi bi-clock-history"></i>No consultations yet.</div></li>
                    @endforelse
                </ul></div>
        </div>
    </div>
@endsection
