@extends('pdf.layout')
@section('body')
    <h2>Daily appointment list – {{ $doctor->name }}</h2>
    <p class="muted">{{ $date->format('l, d F Y') }} · Room {{ $doctor->consulting_room }} · {{ $appointments->count() }} appointment(s)</p>
    <table>
        <thead><tr><th>Time</th><th>Appointment</th><th>Patient no.</th><th>Patient</th><th>Phone</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($appointments as $a)
            <tr><td>{{ $a->time_short }}</td><td>{{ $a->appointment_number }}</td><td>{{ $a->patient->patient_number }}</td>
                <td>{{ $a->patient->full_name }}</td><td>{{ $a->patient->phone }}</td><td>{{ $a->status_label }}</td></tr>
        @empty
            <tr><td colspan="6" class="muted">No appointments on this date.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
