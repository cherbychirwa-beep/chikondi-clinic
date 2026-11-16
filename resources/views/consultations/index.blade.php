@extends('layouts.app')
@section('title', 'Consultations')
@section('content')
    <h1 class="page-title mb-3">Consultation records</h1>

    <form method="GET" class="card card-body mb-3 row g-2 align-items-end flex-row">
        <div class="col-md-4"><label class="form-label" for="q">Patient</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" class="form-control" placeholder="Number, name or phone"></div>
        <div class="col-md-3"><label class="form-label" for="date">Consultation date</label>
            <input type="date" id="date" name="date" value="{{ request('date') }}" class="form-control"></div>
        @include('partials.filter-actions', ['clear' => route('consultations.index')])
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Date</th><th>Patient</th><th>Doctor</th><th>Diagnosis</th><th>Medicines</th><th></th></tr></thead>
                <tbody>
                @forelse($consultations as $c)
                    <tr>
                        <td>{{ $c->consultation_date->format('d M Y') }}</td>
                        <td>{{ $c->patient->full_name }}<div class="small text-muted">{{ $c->patient->patient_number }}</div></td>
                        <td>{{ $c->doctor->name }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($c->diagnosis, 60) }}</td>
                        <td>{{ $c->prescriptions->count() }}</td>
                        <td class="text-end"><a href="{{ route('consultations.show', $c) }}" class="btn btn-sm btn-outline-secondary">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><i class="bi bi-clipboard2"></i>No consultations found.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($consultations->hasPages())<div class="card-footer">{{ $consultations->links() }}</div>@endif
    </div>
@endsection
