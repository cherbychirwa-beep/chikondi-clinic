@extends('pdf.layout')
@section('body')
    <h2>Patient prescription record</h2>
    <table class="kv">
        <tr><td class="muted" width="25%">Patient</td><td><strong>{{ $patient->full_name }}</strong> ({{ $patient->patient_number }})</td></tr>
        <tr><td class="muted">Date of birth</td><td>{{ $patient->date_of_birth->format('d M Y') }} · {{ $patient->age }} years · {{ ucfirst($patient->gender) }}</td></tr>
    </table>

    @forelse($consultations as $c)
        <div class="box">
            <strong>{{ $c->consultation_date->format('d M Y') }}</strong> · {{ $c->doctor->name }}<br>
            <span class="muted">Diagnosis:</span> {{ $c->diagnosis }}
            @if($c->prescriptions->isNotEmpty())
                <table style="margin-top:6px">
                    <thead><tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th></tr></thead>
                    <tbody>
                    @foreach($c->prescriptions as $rx)
                        <tr><td>{{ $rx->medicine->label }}</td><td>{{ $rx->dosage }}</td><td>{{ $rx->frequency }}</td><td>{{ $rx->duration }}</td><td>{{ $rx->instructions }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="muted" style="margin-top:4px">No medicines prescribed.</div>
            @endif
        </div>
    @empty
        <p class="muted">No consultation records available.</p>
    @endforelse
@endsection
