@extends('layouts.app')
@section('title', 'Appointment '.$appointment->appointment_number)
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('appointments.index') }}">Appointments</a></li>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h1 class="page-title mb-0">Appointment confirmation</h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
            @can('appointments.cancel')
                @if(in_array($appointment->status, ['scheduled', 'checked_in']))
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal"
                            data-action="{{ route('appointments.cancel', $appointment) }}" data-label="{{ $appointment->appointment_number }} – {{ $appointment->patient->full_name }}">Cancel appointment</button>
                @endif
            @endcan
        </div>
    </div>

    <div class="card mx-auto" style="max-width:720px">
        <div class="card-body p-4">
            <div class="text-center border-bottom pb-3 mb-3">
                <div class="fw-bold fs-4 text-primary">{{ config('chikondi.name') }}</div>
                <div class="small text-muted">{{ config('chikondi.tagline') }} · {{ config('chikondi.address') }} · {{ config('chikondi.phone') }}</div>
                <div class="mt-2 text-uppercase small fw-semibold">Appointment confirmation</div>
            </div>

            <div class="row g-3">
                <div class="col-sm-6"><div class="small text-muted">Appointment number</div><div class="fw-bold fs-5">{{ $appointment->appointment_number }}</div></div>
                <div class="col-sm-6"><div class="small text-muted">Status</div><x-status-badge :status="$appointment->status" /></div>
                <div class="col-sm-6"><div class="small text-muted">Patient</div><div class="fw-semibold">{{ $appointment->patient->full_name }}</div>
                    <div class="small">{{ $appointment->patient->patient_number }}</div></div>
                <div class="col-sm-6"><div class="small text-muted">Doctor</div><div class="fw-semibold">{{ $appointment->doctor->name }}</div>
                    <div class="small">Consulting room {{ $appointment->doctor->consulting_room }}</div></div>
                <div class="col-sm-6"><div class="small text-muted">Date</div><div class="fw-semibold">{{ $appointment->appointment_date->format('l, d F Y') }}</div></div>
                <div class="col-sm-6"><div class="small text-muted">Time</div><div class="fw-semibold">{{ $appointment->time_short }}</div></div>
                <div class="col-sm-6"><div class="small text-muted">Booked by</div><div>{{ $appointment->bookedBy->name }}</div></div>
                <div class="col-sm-6"><div class="small text-muted">Booked on</div><div>{{ $appointment->created_at->format('d M Y H:i') }}</div></div>
                @if($appointment->checked_in_at)
                    <div class="col-sm-6"><div class="small text-muted">Checked in</div><div>{{ $appointment->checked_in_at->format('d M Y H:i') }} by {{ $appointment->checkedInBy?->name }}</div></div>
                @endif
                @if($appointment->cancellation)
                    <div class="col-12"><div class="alert alert-danger mb-0"><strong>Cancelled</strong> on {{ $appointment->cancellation->cancelled_at->format('d M Y H:i') }}
                        by {{ $appointment->cancellation->cancelledBy->name }}.<br>Reason: {{ $appointment->cancellation->reason }}</div></div>
                @endif
            </div>

            <p class="small text-muted mt-4 mb-0">Please arrive 15 minutes early and bring this confirmation or your patient number. Contact the centre to reschedule.</p>
        </div>
    </div>
    @can('appointments.cancel')@include('partials.cancel-modal')@endcan
@endsection
