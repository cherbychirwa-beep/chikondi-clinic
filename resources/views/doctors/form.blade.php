@extends('layouts.app')
@section('title', $doctor ? 'Edit doctor' : 'Register doctor')
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('doctors.index') }}">Doctors</a></li>@endsection
@section('content')
    @php($type = old('employment_type', $doctor?->employment_type ?? 'full_time'))
    <div class="card" style="max-width:900px">
        <div class="card-body p-4">
            <form method="POST" action="{{ $doctor ? route('doctors.update', $doctor) : route('doctors.store') }}" class="row g-3">
                @csrf
                @if($doctor) @method('PUT') @endif

                <div class="col-12">
                    <label class="form-label d-block">Employment type <span class="text-danger">*</span></label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="employment_type" id="type_ft" value="full_time" @checked($type === 'full_time')>
                        <label class="btn btn-outline-primary" for="type_ft">Full-time doctor</label>
                        <input type="radio" class="btn-check" name="employment_type" id="type_vs" value="visiting" @checked($type === 'visiting')>
                        <label class="btn btn-outline-primary" for="type_vs">Visiting doctor</label>
                    </div>
                    @error('employment_type')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <x-field name="name" label="Full name" :value="$doctor?->user->name" :required="true" maxlength="120" />
                <x-field name="doctor_number" label="Doctor number" :value="$doctor?->doctor_number" :required="(bool) $doctor" maxlength="20"
                         :help="$doctor ? '' : 'Leave blank to generate automatically (e.g. DR-005).'" />
                <x-field name="phone" label="Telephone" :value="$doctor?->phone" :required="true" maxlength="20" />
                <x-field name="consulting_room" label="Consulting room number" :value="$doctor?->consulting_room" :required="true" maxlength="20" />
                <x-field name="email" label="Email (optional)" type="email" :value="$doctor?->user->email" maxlength="255" />

                <div class="col-12" id="visitFields" style="{{ $type === 'visiting' ? '' : 'display:none' }}">
                    <div class="row g-3 p-3 rounded" style="background:var(--cc-teal-soft)">
                        <x-field name="next_visit_date" label="Next visit date" type="date" :value="$doctor?->next_visit_date?->toDateString()" />
                        <x-field name="next_visit_duration_days" label="Next visit duration (days)" type="number" :value="$doctor?->next_visit_duration_days" min="1" max="60"
                                 help="Appointments can only be booked inside this window, on the doctor's scheduled weekdays." />
                    </div>
                </div>

                @unless($doctor)
                    <div class="col-12"><hr class="my-1"><div class="small text-muted">Login account for this doctor</div></div>
                    <x-field name="username" label="Username" :required="true" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" />
                    <x-field name="password" label="Temporary password" type="password" :required="true" minlength="8" autocomplete="new-password" help="The doctor must change it at first login." />
                @endunless

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $doctor ? 'Save changes' : 'Register doctor' }}</button>
                    <a href="{{ $doctor ? route('doctors.show', $doctor) : route('doctors.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('scripts')
<script>
    const toggleVisit = () => {
        const visiting = document.getElementById('type_vs').checked;
        document.getElementById('visitFields').style.display = visiting ? '' : 'none';
        document.getElementById('next_visit_date').required = visiting;
        document.getElementById('next_visit_duration_days').required = visiting;
    };
    document.querySelectorAll('input[name=employment_type]').forEach(r => r.addEventListener('change', toggleVisit));
    toggleVisit();
</script>
@endpush
