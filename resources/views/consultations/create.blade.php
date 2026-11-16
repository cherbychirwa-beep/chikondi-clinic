@extends('layouts.app')
@section('title', 'Consultation')
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('consultations.queue') }}">Queue</a></li>@endsection
@section('content')
    @php($oldRows = old('prescriptions', []))
    @php($nextIndex = collect($oldRows)->keys()->map(fn ($k) => (int) $k)->max())
    @php($nextIndex = is_null($nextIndex) ? 0 : $nextIndex + 1)

    <div class="row g-3">
        {{-- Left: patient and history --}}
        <div class="col-lg-4">
            <div class="card mb-3"><div class="card-header">Patient</div>
                <div class="card-body">
                    <div class="fw-bold fs-5">{{ $patient->full_name }}</div>
                    <div class="text-muted small mb-2">{{ $patient->patient_number }}</div>
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">Age / gender</dt><dd class="col-7">{{ $patient->age }} years · {{ ucfirst($patient->gender) }}</dd>
                        <dt class="col-5 text-muted">Date of birth</dt><dd class="col-7">{{ $patient->date_of_birth->format('d M Y') }}</dd>
                        <dt class="col-5 text-muted">Telephone</dt><dd class="col-7">{{ $patient->phone }}</dd>
                        <dt class="col-5 text-muted">Next of kin</dt><dd class="col-7">{{ $patient->next_of_kin_name }}<br>{{ $patient->next_of_kin_phone }}</dd>
                    </dl>
                </div></div>
            <div class="card mb-3"><div class="card-header">Appointment</div>
                <div class="card-body small">
                    {{ $appointment->appointment_number }}<br>
                    {{ $appointment->appointment_date->format('d M Y') }} at {{ $appointment->time_short }}<br>
                    Checked in {{ $appointment->checked_in_at?->format('H:i') }}
                </div></div>
            <div class="card"><div class="card-header">Previous consultations (yours)</div>
                <div class="list-group list-group-flush">
                    @forelse($history as $h)
                        <div class="list-group-item small">
                            <strong>{{ $h->consultation_date->format('d M Y') }}</strong> – {{ $h->diagnosis }}
                            @if($h->prescriptions->isNotEmpty())
                                <div class="text-muted">Rx: {{ $h->prescriptions->map(fn ($p) => $p->medicine->name.' '.$p->dosage)->join('; ') }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="list-group-item"><div class="empty-state py-3">No previous consultations.</div></div>
                    @endforelse
                </div></div>
        </div>

        {{-- Right: consultation form --}}
        <div class="col-lg-8">
            <form method="POST" action="{{ route('consultations.store', $appointment) }}" class="card" data-confirm="Save this consultation? The appointment will be marked as completed.">
                @csrf
                <div class="card-header">Consultation</div>
                <div class="card-body row g-3">
                    <x-field name="consultation_date" label="Consultation date" type="date" :value="today()->toDateString()" :required="true"
                             :max="today()->toDateString()" :min="$appointment->appointment_date->toDateString()" col="col-md-4" />
                    <x-textarea name="diagnosis" label="Diagnosis" :required="true" rows="2" maxlength="2000" />
                    <x-textarea name="observations" label="Clinical observations" rows="3" maxlength="5000" />
                    <x-textarea name="notes" label="Consultation notes" rows="3" maxlength="5000" />

                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h2 class="h6 mb-0">Prescriptions <span class="text-muted fw-normal">(optional)</span></h2>
                            <button type="button" class="btn btn-sm btn-outline-teal" id="addRx"><i class="bi bi-plus-lg me-1"></i>Add medicine</button>
                        </div>
                        @error('prescriptions')<div class="text-danger small">{{ $message }}</div>@enderror
                        <div id="rxRows">
                            @foreach($oldRows as $i => $row)
                                @include('consultations._rx_row', ['i' => $i, 'row' => $row, 'medicines' => $medicines])
                            @endforeach
                        </div>
                        <div id="rxEmpty" class="text-muted small {{ count($oldRows) ? 'd-none' : '' }}">No medicines added – this consultation will be saved without a prescription.</div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save consultation</button>
                    <a href="{{ route('consultations.queue') }}" class="btn btn-light">Back to queue</a>
                </div>
            </form>
        </div>
    </div>

    <template id="rxTemplate">
        @include('consultations._rx_row', ['i' => '__i__', 'row' => [], 'medicines' => $medicines])
    </template>
@endsection
@push('scripts')
<script>
    let nextRx = {{ $nextIndex }};
    const rows = document.getElementById('rxRows');
    const empty = document.getElementById('rxEmpty');
    const refresh = () => empty.classList.toggle('d-none', rows.children.length > 0);

    document.getElementById('addRx').addEventListener('click', () => {
        const html = document.getElementById('rxTemplate').innerHTML.replaceAll('__i__', nextRx++);
        rows.insertAdjacentHTML('beforeend', html);
        refresh();
    });
    rows.addEventListener('click', e => {
        const btn = e.target.closest('.remove-rx');
        if (btn) { btn.closest('.rx-row').remove(); refresh(); }
    });
</script>
@endpush
