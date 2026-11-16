@extends('layouts.app')
@section('title', 'Payment records')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title mb-0">Payment records</h1>
        @can('payments.record')<a href="{{ route('payments.pending') }}" class="btn btn-teal"><i class="bi bi-cash-coin me-1"></i>Record a payment</a>@endcan
    </div>

    <form method="GET" class="card card-body mb-3 row g-2 align-items-end flex-row">
        <div class="col-md-2"><label class="form-label" for="receipt">Receipt no.</label>
            <input type="text" id="receipt" name="receipt" value="{{ request('receipt') }}" class="form-control" placeholder="RCT-"></div>
        <div class="col-md-2"><label class="form-label" for="patient_number">Patient no.</label>
            <input type="text" id="patient_number" name="patient_number" value="{{ request('patient_number') }}" class="form-control" placeholder="CC-"></div>
        <div class="col-md-2"><label class="form-label" for="method">Method</label>
            <select name="method" id="method" class="form-select"><option value="">All</option>
                @foreach(\App\Models\Payment::METHODS as $k => $v)<option value="{{ $k }}" @selected(request('method') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label" for="status">Status</label>
            <select name="status" id="status" class="form-select"><option value="">All</option>
                @foreach(\App\Models\Payment::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label" for="date">Payment date</label>
            <input type="date" id="date" name="date" value="{{ request('date') }}" class="form-control"></div>
        @include('partials.filter-actions', ['clear' => route('payments.index')])
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Receipt</th><th>Date</th><th>Patient</th><th>Method</th><th class="text-end">Fee</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td class="fw-semibold">{{ $p->receipt_number }}</td>
                        <td>{{ $p->paid_at->format('d M Y H:i') }}</td>
                        <td>{{ $p->patient->full_name }}<div class="small text-muted">{{ $p->patient->patient_number }}</div></td>
                        <td>{{ $p->method_label }}</td>
                        <td class="text-end">{{ money($p->consultation_fee) }}</td>
                        <td class="text-end">{{ money($p->amount_paid) }}</td>
                        <td class="text-end">{{ money($p->balance_after) }}</td>
                        <td><x-status-badge :status="$p->payment_status" /></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('payments.show', $p) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('payments.receipt', $p) }}" class="btn btn-sm btn-outline-primary" title="Download PDF receipt"><i class="bi bi-file-earmark-pdf"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="empty-state"><i class="bi bi-receipt"></i>No payments match your filters.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())<div class="card-footer">{{ $payments->links() }}</div>@endif
    </div>
@endsection
