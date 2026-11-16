@extends('layouts.app')
@section('title', 'Consultation record')
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('consultations.index') }}">Consultations</a></li>@endsection
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title mb-0">{{ $consultation->patient->full_name }}</h1>
            <div class="text-muted">{{ $consultation->patient->patient_number }} · consulted {{ $consultation->consultation_date->format('d M Y') }} by {{ $consultation->doctor->name }}</div>
        </div>
        <div class="d-flex gap-2 no-print">
            @can('prescriptions.view')
                <a href="{{ route('patients.prescriptions', $consultation->patient) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Prescription record (PDF)</a>
            @endcan
            <button type="button" class="btn btn-light" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3"><div class="card-header">Clinical record</div>
                <div class="card-body">
                    <h2 class="h6 text-muted">Diagnosis</h2><p>{{ $consultation->diagnosis }}</p>
                    <h2 class="h6 text-muted">Clinical observations</h2><p>{!! nl2br(e($consultation->observations ?: '—')) !!}</p>
                    <h2 class="h6 text-muted">Consultation notes</h2><p class="mb-0">{!! nl2br(e($consultation->notes ?: '—')) !!}</p>
                </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-3"><div class="card-header">Prescriptions</div>
                <ul class="list-group list-group-flush">
                    @forelse($consultation->prescriptions as $rx)
                        <li class="list-group-item">
                            <strong>{{ $rx->medicine->label }}</strong>
                            <div class="small">{{ $rx->dosage }} · {{ $rx->frequency }} · {{ $rx->duration }}</div>
                            @if($rx->instructions)<div class="small text-muted">{{ $rx->instructions }}</div>@endif
                        </li>
                    @empty
                        <li class="list-group-item"><div class="empty-state py-3">No medicines prescribed.</div></li>
                    @endforelse
                </ul></div>
            <div class="card"><div class="card-body small">
                    <div class="d-flex justify-content-between"><span class="text-muted">Appointment</span><span>{{ $consultation->appointment->appointment_number }}</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Fee charged</span><span>{{ money($consultation->fee_charged) }}</span></div>
                </div></div>
        </div>
    </div>
@endsection
