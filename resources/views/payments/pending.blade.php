@extends('layouts.app')
@section('title', 'Pending payments')
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Payments</a></li>@endsection
@section('content')
    <h1 class="page-title mb-3">Consultations awaiting payment</h1>

    <form method="GET" class="card card-body mb-3 row g-2 align-items-end flex-row">
        <div class="col-md-5"><label class="form-label" for="q">Patient</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" class="form-control" placeholder="Patient number, name or phone"></div>
        @include('partials.filter-actions', ['clear' => route('payments.pending')])
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Consultation</th><th>Patient</th><th>Doctor</th><th class="text-end">Fee</th><th class="text-end">Paid</th><th class="text-end">Outstanding</th><th></th></tr></thead>
                <tbody>
                @forelse($consultations as $c)
                    @php($paid = (float) ($c->paid_total ?? 0))
                    <tr>
                        <td>{{ $c->consultation_date->format('d M Y') }}<div class="small text-muted">#{{ $c->id }}</div></td>
                        <td>{{ $c->patient->full_name }}<div class="small text-muted">{{ $c->patient->patient_number }}</div></td>
                        <td>{{ $c->doctor->name }}</td>
                        <td class="text-end">{{ money($c->fee_charged) }}</td>
                        <td class="text-end">{{ money($paid) }}</td>
                        <td class="text-end fw-semibold text-danger">{{ money((float) $c->fee_charged - $paid) }}</td>
                        <td class="text-end"><a href="{{ route('payments.create', $c) }}" class="btn btn-sm btn-teal">Record payment</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-check2-all"></i>No outstanding balances.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($consultations->hasPages())<div class="card-footer">{{ $consultations->links() }}</div>@endif
    </div>
@endsection
