@extends('layouts.app')
@section('title', 'Record payment')
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('payments.pending') }}">Pending payments</a></li>@endsection
@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card"><div class="card-header">Consultation details</div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Patient</span><span class="fw-semibold">{{ $consultation->patient->full_name }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Patient no.</span><span>{{ $consultation->patient->patient_number }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Doctor</span><span>{{ $consultation->doctor->name }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Consultation date</span><span>{{ $consultation->consultation_date->format('d M Y') }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Consultation fee</span><span>{{ money($consultation->fee_charged) }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Already paid</span><span>{{ money($paid) }}</span></li>
                    <li class="list-group-item d-flex justify-content-between fs-6"><span class="fw-semibold">Outstanding</span><span class="fw-bold text-danger">{{ money($outstanding) }}</span></li>
                </ul></div>
            @if($previous->isNotEmpty())
                <div class="card mt-3"><div class="card-header">Previous payments</div>
                    <ul class="list-group list-group-flush small">
                        @foreach($previous as $p)
                            <li class="list-group-item d-flex justify-content-between"><span>{{ $p->receipt_number }} · {{ $p->paid_at->format('d M H:i') }}</span><span>{{ money($p->amount_paid) }}</span></li>
                        @endforeach
                    </ul></div>
            @endif
        </div>
        <div class="col-lg-7">
            <div class="card"><div class="card-header">Payment</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('payments.store', $consultation) }}" class="row g-3"
                          data-confirm="Record this payment? A receipt will be generated and it cannot be edited afterwards.">
                        @csrf
                        <input type="hidden" name="submission_token" value="{{ old('submission_token', $token) }}">
                        <x-field name="amount_paid" label="Amount paid (MWK)" type="number" :required="true" col="col-12" step="0.01" min="0.01" :max="$outstanding" :value="$outstanding"
                                 help="Enter less than the outstanding balance to record a partial payment." />
                        <x-select name="payment_method" label="Payment method" :required="true" col="col-12" :options="\App\Models\Payment::METHODS" placeholder="Select method…" />
                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-teal"><i class="bi bi-check2-circle me-1"></i>Record payment</button>
                            <a href="{{ route('payments.pending') }}" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div></div>
        </div>
    </div>
@endsection
