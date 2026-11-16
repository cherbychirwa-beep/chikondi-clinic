@extends('layouts.app')
@section('title', 'Consultation fee')
@section('content')
    <h1 class="page-title mb-3">Consultation fee</h1>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card mb-3"><div class="card-body">
                    <div class="text-muted small">Fee currently in force</div>
                    <div class="display-6 fw-bold text-primary">{{ money($current?->amount ?? 0) }}</div>
                    @if($current)<div class="small text-muted">Effective from {{ $current->effective_from->format('d M Y') }}</div>
                    @else<div class="small text-danger">No fee configured yet – consultations will be charged {{ money(0) }}.</div>@endif
                </div></div>
            <div class="card"><div class="card-header">Set a new fee</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('fees.store') }}" class="row g-3"
                          data-confirm="Save this consultation fee? Existing consultations keep the fee they were charged.">
                        @csrf
                        <x-field name="amount" label="Amount (MWK)" type="number" :required="true" col="col-12" step="0.01" min="0" max="99999999" />
                        <x-field name="effective_from" label="Effective from" type="date" :value="today()->toDateString()" :required="true" col="col-12" :min="today()->toDateString()" />
                        <x-field name="note" label="Note (optional)" col="col-12" maxlength="255" />
                        <div class="col-12"><button class="btn btn-primary">Save fee</button></div>
                    </form>
                </div></div>
        </div>
        <div class="col-lg-7">
            <div class="card"><div class="card-header">Fee history</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Effective from</th><th class="text-end">Amount</th><th>Set by</th><th>Note</th></tr></thead>
                        <tbody>
                        @forelse($history as $f)
                            <tr><td>{{ $f->effective_from->format('d M Y') }}</td><td class="text-end">{{ money($f->amount) }}</td>
                                <td>{{ $f->createdBy->name }}</td><td class="small text-muted">{{ $f->note }}</td></tr>
                        @empty
                            <tr><td colspan="4"><div class="empty-state">No fee history.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($history->hasPages())<div class="card-footer">{{ $history->links() }}</div>@endif
            </div>
        </div>
    </div>
@endsection
