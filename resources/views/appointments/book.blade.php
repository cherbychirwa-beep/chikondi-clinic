@extends('layouts.app')
@section('title', 'Book appointment')
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('appointments.index') }}">Appointments</a></li>@endsection
@section('content')
    <h1 class="page-title mb-3">Book an appointment</h1>

    {{-- Step 1: find the patient --}}
    <div class="card mb-3">
        <div class="card-header">1 · Find the patient</div>
        <div class="card-body">
            <form method="GET" action="{{ route('appointments.book') }}" class="row g-2">
                <div class="col-md-6">
                    <label for="q" class="form-label">Patient number or date of birth</label>
                    <input type="text" id="q" name="q" value="{{ request('q') }}" class="form-control" placeholder="CC-000001  or  1990-05-21  (name also works)">
                </div>
                <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button></div>
            </form>

            @if($matches !== null)
                <div class="mt-3">
                    @forelse($matches as $m)
                        <a href="{{ route('appointments.book', ['patient_id' => $m->id]) }}" class="list-group-item list-group-item-action border rounded mb-2 d-flex justify-content-between">
                            <span><strong>{{ $m->full_name }}</strong> <span class="text-muted">· {{ $m->patient_number }} · DOB {{ $m->date_of_birth->format('d M Y') }}</span></span>
                            <span class="text-muted small">{{ $m->primaryDoctor?->name }}</span>
                        </a>
                    @empty
                        <div class="empty-state"><i class="bi bi-person-x"></i>No registered patient matches "{{ request('q') }}".</div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>

    @if($patient)
        <div class="card mb-3 border-teal">
            <div class="card-body d-flex flex-wrap justify-content-between gap-2">
                <div><div class="fw-bold fs-5">{{ $patient->full_name }}</div>
                    <div class="text-muted small">{{ $patient->patient_number }} · DOB {{ $patient->date_of_birth->format('d M Y') }} · {{ $patient->phone }}</div></div>
                <div class="text-md-end"><div class="small text-muted">Registered doctor</div><div class="fw-semibold">{{ $patient->primaryDoctor?->name ?? 'None assigned' }}</div></div>
            </div>
        </div>

        {{-- Step 2: choose date --}}
        <div class="card mb-3">
            <div class="card-header">2 · Choose a date</div>
            <div class="card-body">
                <form method="GET" action="{{ route('appointments.book') }}" class="row g-2 align-items-end">
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                    <div class="col-md-3"><label class="form-label" for="date">Appointment date</label>
                        <input type="date" id="date" name="date" class="form-control" value="{{ $date->toDateString() }}" min="{{ today()->toDateString() }}"></div>
                    <div class="col-auto"><button class="btn btn-primary">Show slots</button></div>
                    <div class="col-auto"><a class="btn btn-light" href="{{ route('appointments.book', ['patient_id' => $patient->id, 'date' => $date->copy()->addDay()->toDateString()]) }}">Next day <i class="bi bi-chevron-right"></i></a></div>
                </form>
            </div>
        </div>

        {{-- Step 3: choose a slot --}}
        <div class="card mb-3">
            <div class="card-header">3 · Pick a time slot – {{ $date->format('l, d F Y') }}</div>
            <div class="card-body">
                @if($patient->primaryDoctor)
                    <h2 class="h6">{{ $patient->primaryDoctor->name }} <span class="text-muted fw-normal">(registered doctor · Room {{ $patient->primaryDoctor->consulting_room }})</span></h2>
                    @if($primarySlots->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach($primarySlots as $slot)
                                <form method="POST" action="{{ route('appointments.store') }}" data-no-loading
                                      data-confirm="Book {{ $slot['time'] }} on {{ $date->format('d M Y') }} with {{ $patient->primaryDoctor->name }} for {{ $patient->full_name }}?">
                                    @csrf
                                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                    <input type="hidden" name="doctor_id" value="{{ $patient->primaryDoctor->id }}">
                                    <input type="hidden" name="appointment_date" value="{{ $date->toDateString() }}">
                                    <input type="hidden" name="appointment_time" value="{{ $slot['time'] }}">
                                    <button class="btn btn-outline-teal slot-btn">{{ $slot['time'] }}</button>
                                </form>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-warning py-2">{{ $patient->primaryDoctor->name }} has no free slots on this date
                            ({{ $patient->primaryDoctor->domain()->availabilitySummary() }}). Alternative doctors are shown below.</div>
                    @endif
                @else
                    <div class="alert alert-warning py-2">This patient has no registered doctor. Choose any available doctor below.</div>
                @endif

                @if($alternatives->isNotEmpty())
                    <hr>
                    <h2 class="h6 text-muted">Alternative doctors available on this date</h2>
                    @foreach($alternatives as $alt)
                        <div class="mb-3">
                            <div class="fw-semibold">{{ $alt['doctor']->name }} <span class="badge text-bg-{{ $alt['doctor']->isVisiting() ? 'warning' : 'primary' }}">{{ $alt['doctor']->domain()->employmentLabel() }}</span>
                                <span class="text-muted small fw-normal">Room {{ $alt['doctor']->consulting_room }}</span></div>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @foreach($alt['slots'] as $slot)
                                    <form method="POST" action="{{ route('appointments.store') }}" data-no-loading
                                          data-confirm="Book {{ $slot['time'] }} on {{ $date->format('d M Y') }} with {{ $alt['doctor']->name }} for {{ $patient->full_name }}?">
                                        @csrf
                                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                        <input type="hidden" name="doctor_id" value="{{ $alt['doctor']->id }}">
                                        <input type="hidden" name="appointment_date" value="{{ $date->toDateString() }}">
                                        <input type="hidden" name="appointment_time" value="{{ $slot['time'] }}">
                                        <button class="btn btn-outline-primary slot-btn">{{ $slot['time'] }}</button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @elseif($primarySlots->isNotEmpty())
                    <a href="{{ route('appointments.book', ['patient_id' => $patient->id, 'date' => $date->toDateString(), 'show_all' => 1]) }}" class="small">Show other doctors too</a>
                @elseif($patient->primaryDoctor)
                    <div class="empty-state"><i class="bi bi-calendar-x"></i>No doctor has free slots on this date. Try another day.</div>
                @endif
            </div>
        </div>
    @endif
@endsection
