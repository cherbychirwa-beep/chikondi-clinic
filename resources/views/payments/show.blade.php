@extends('layouts.app')
@section('title', 'Receipt '.$payment->receipt_number)
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payments</a></li>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h1 class="page-title mb-0">Payment receipt</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
            @if($payment->payment_status === 'partial')
                @can('payments.record')<a href="{{ route('payments.create', $payment->consultation) }}" class="btn btn-teal">Record balance</a>@endcan
            @endif
        </div>
    </div>

    <div class="card mx-auto" style="max-width:640px">
        <div class="card-body p-4">
            <div class="text-center border-bottom pb-3 mb-3">
                <div class="fw-bold fs-4 text-primary">{{ config('chikondi.name') }}</div>
                <div class="small text-muted">{{ config('chikondi.address') }} · {{ config('chikondi.phone') }}</div>
                <div class="mt-2 text-uppercase small fw-semibold">Official payment receipt</div>
            </div>
            <div class="row g-2 small">
                <div class="col-6 text-muted">Receipt number</div><div class="col-6 fw-bold">{{ $payment->receipt_number }}</div>
                <div class="col-6 text-muted">Date &amp; time</div><div class="col-6">{{ $payment->paid_at->format('d M Y H:i') }}</div>
                <div class="col-6 text-muted">Patient</div><div class="col-6">{{ $payment->patient->full_name }} ({{ $payment->patient->patient_number }})</div>
                <div class="col-6 text-muted">Doctor</div><div class="col-6">{{ $payment->consultation->doctor->name }}</div>
                <div class="col-6 text-muted">Consultation date</div><div class="col-6">{{ $payment->consultation->consultation_date->format('d M Y') }}</div>
                <div class="col-6 text-muted">Payment method</div><div class="col-6">{{ $payment->method_label }}</div>
                <div class="col-6 text-muted">Received by</div><div class="col-6">{{ $payment->recordedBy->name }}</div>
            </div>
            <hr>
            <div class="d-flex justify-content-between"><span>Consultation fee</span><span>{{ money($payment->consultation_fee) }}</span></div>
            <div class="d-flex justify-content-between fw-bold fs-5"><span>Amount paid</span><span>{{ money($payment->amount_paid) }}</span></div>
            <div class="d-flex justify-content-between"><span>Balance remaining</span><span>{{ money($payment->balance_after) }}</span></div>
            <div class="text-center mt-3"><x-status-badge :status="$payment->payment_status" /></div>
        </div>
    </div>
@endsection
