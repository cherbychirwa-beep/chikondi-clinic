@extends('layouts.app')
@section('title', 'Daily schedule')
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('appointments.index') }}">Appointments</a></li>@endsection
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="page-title mb-0">Schedule – {{ $date->format('l, d F Y') }}</h1>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <a class="btn btn-light" href="{{ route('appointments.schedule', ['date' => $date->copy()->subDay()->toDateString()]) }}"><i class="bi bi-chevron-left"></i></a>
            <input type="date" name="date" value="{{ $date->toDateString() }}" class="form-control" aria-label="Date">
            <button class="btn btn-primary">Go</button>
            <a class="btn btn-light" href="{{ route('appointments.schedule', ['date' => $date->copy()->addDay()->toDateString()]) }}"><i class="bi bi-chevron-right"></i></a>
        </form>
    </div>

    <div class="small text-muted mb-2">
        <span class="badge text-bg-success">Free</span> <span class="badge text-bg-primary">Booked</span>
        <span class="badge text-bg-secondary">Past</span>
    </div>

    <div class="row g-3">
        @forelse($rows as $row)
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between">
                        <span>{{ $row['doctor']->name }}</span>
                        <span class="badge text-bg-{{ $row['doctor']->isVisiting() ? 'warning' : 'primary' }}">{{ $row['doctor']->domain()->employmentLabel() }}</span>
                    </div>
                    <div class="card-body">
                        @if($row['slots']->isEmpty())
                            <div class="text-muted small">Not available on this date ({{ $row['doctor']->domain()->availabilitySummary() }}).</div>
                        @else
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($row['slots'] as $slot)
                                    @php($booking = $row['bookings']->get($slot['time']))
                                    @if($booking)
                                        <a href="{{ route('appointments.show', $booking) }}" class="btn btn-sm btn-primary text-start" title="{{ $booking->appointment_number }}">
                                            <strong>{{ $slot['time'] }}</strong><br><span class="small">{{ \Illuminate\Support\Str::limit($booking->patient->full_name, 18) }}</span></a>
                                    @elseif($slot['past'])
                                        <span class="btn btn-sm btn-secondary disabled">{{ $slot['time'] }}</span>
                                    @else
                                        <span class="btn btn-sm btn-success disabled">{{ $slot['time'] }}</span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card"><div class="empty-state"><i class="bi bi-person-badge"></i>No active doctors.</div></div></div>
        @endforelse
    </div>
@endsection
