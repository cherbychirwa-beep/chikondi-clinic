@extends('layouts.app')
@section('title', 'Consultation queue')
@section('content')
    <h1 class="page-title mb-3">Today's consultation queue <span class="text-muted fs-6">· {{ today()->format('d F Y') }}</span></h1>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Time</th><th>Patient</th><th>Status</th><th>Checked in</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                @forelse($appointments as $a)
                    <tr>
                        <td class="fw-semibold">{{ $a->time_short }}</td>
                        <td>{{ $a->patient->full_name }}<div class="small text-muted">{{ $a->patient->patient_number }} · {{ $a->patient->age }}y {{ ucfirst($a->patient->gender) }}</div></td>
                        <td><x-status-badge :status="$a->status" /></td>
                        <td>{{ $a->checked_in_at?->format('H:i') ?? '—' }}</td>
                        <td class="text-end">
                            @if($a->status === 'checked_in')
                                <a href="{{ route('consultations.create', $a) }}" class="btn btn-sm btn-teal"><i class="bi bi-journal-medical me-1"></i>Start consultation</a>
                            @elseif($a->status === 'completed' && $a->consultation)
                                <a href="{{ route('consultations.show', $a->consultation) }}" class="btn btn-sm btn-outline-secondary">View record</a>
                            @else
                                <span class="small text-muted">Awaiting check-in</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state"><i class="bi bi-person-check"></i>No appointments in your queue today.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
