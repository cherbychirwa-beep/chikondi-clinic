@extends('layouts.app')
@section('title', $patient->full_name)
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('patients.index') }}">Patients</a></li>@endsection
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title mb-0">{{ $patient->full_name }}</h1>
            <div class="text-muted">{{ $patient->patient_number }} · {{ $patient->age }} years · {{ ucfirst($patient->gender) }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('patients.update')<a href="{{ route('patients.edit', $patient) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Update</a>@endcan
            @can('appointments.book')<a href="{{ route('appointments.book', ['patient_id' => $patient->id]) }}" class="btn btn-teal"><i class="bi bi-calendar-plus me-1"></i>Book appointment</a>@endcan
            @can('prescriptions.view')<a href="{{ route('patients.prescriptions', $patient) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Prescription record (PDF)</a>@endcan
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card"><div class="card-header">Registration details</div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Date of birth</span><span>{{ $patient->date_of_birth->format('d M Y') }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Telephone</span><span>{{ $patient->phone }}</span></li>
                    <li class="list-group-item"><span class="text-muted d-block">Address</span>{{ $patient->address }}</li>
                    <li class="list-group-item"><span class="text-muted d-block">Next of kin</span>{{ $patient->next_of_kin_name }} · {{ $patient->next_of_kin_phone }}</li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Registered doctor</span><span>{{ $patient->primaryDoctor?->name ?? '—' }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Registered on</span><span>{{ $patient->registered_on->format('d M Y') }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Registered by</span><span>{{ $patient->registeredBy?->name }}</span></li>
                </ul></div>
        </div>

        <div class="col-lg-8">
            @if($appointments !== null)
                <div class="card mb-3"><div class="card-header">Appointment history</div>
                    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Appointment</th><th>Date</th><th>Doctor</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @forelse($appointments as $a)
                            <tr><td>{{ $a->appointment_number }}</td><td>{{ $a->appointment_date->format('d M Y') }} {{ $a->time_short }}</td>
                                <td>{{ $a->doctor->name }}</td><td><x-status-badge :status="$a->status" /></td>
                                <td class="text-end"><a href="{{ route('appointments.show', $a) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state">No appointments recorded.</div></td></tr>
                        @endforelse
                        </tbody></table></div></div>
            @endif

            @if($consultations !== null)
                <div class="card mb-3"><div class="card-header">Consultation &amp; prescription history</div>
                    <div class="list-group list-group-flush">
                        @forelse($consultations as $c)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $c->consultation_date->format('d M Y') }} · {{ $c->doctor->name }}</strong>
                                    <a href="{{ route('consultations.show', $c) }}" class="small">Open</a>
                                </div>
                                <div class="small"><span class="text-muted">Diagnosis:</span> {{ $c->diagnosis }}</div>
                                @if($c->prescriptions->isNotEmpty())
                                    <div class="small text-muted mt-1">Prescribed: {{ $c->prescriptions->map(fn ($p) => $p->medicine->name)->join(', ') }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="list-group-item"><div class="empty-state">No consultations on record{{ auth()->user()->hasRole('doctor') ? ' with you' : '' }}.</div></div>
                        @endforelse
                    </div></div>
            @endif

            @if($payments !== null)
                <div class="card"><div class="card-header">Payment history</div>
                    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Receipt</th><th>Date</th><th class="text-end">Fee</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($payments as $pay)
                            <tr><td><a href="{{ route('payments.show', $pay) }}">{{ $pay->receipt_number }}</a></td><td>{{ $pay->paid_at->format('d M Y H:i') }}</td>
                                <td class="text-end">{{ money($pay->consultation_fee) }}</td><td class="text-end">{{ money($pay->amount_paid) }}</td>
                                <td class="text-end">{{ money($pay->balance_after) }}</td><td><x-status-badge :status="$pay->payment_status" /></td></tr>
                        @empty
                            <tr><td colspan="6"><div class="empty-state">No payments recorded.</div></td></tr>
                        @endforelse
                        </tbody></table></div></div>
            @endif
        </div>
    </div>
@endsection
