@extends('layouts.app')
@section('title', $patient ? 'Update patient' : 'Register patient')
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('patients.index') }}">Patients</a></li>@endsection
@section('content')
    <div class="card" style="max-width:900px">
        <div class="card-body p-4">
            @if($patient)
                <div class="alert alert-light border small">Patient number <strong>{{ $patient->patient_number }}</strong> · registered {{ $patient->registered_on->format('d M Y') }}</div>
            @else
                <div class="alert alert-light border small"><i class="bi bi-info-circle me-1"></i>The patient number is generated automatically when you save.</div>
            @endif
            <form method="POST" action="{{ $patient ? route('patients.update', $patient) : route('patients.store') }}" class="row g-3">
                @csrf
                @if($patient) @method('PUT') @endif

                <x-field name="full_name" label="Full name" :value="$patient?->full_name" :required="true" minlength="3" maxlength="150" />
                <x-field name="date_of_birth" label="Date of birth" type="date" :value="$patient?->date_of_birth?->toDateString()" :required="true" :max="today()->toDateString()" min="1900-01-02" />
                <x-select name="gender" label="Gender" :required="true" :value="$patient?->gender" :options="['male' => 'Male', 'female' => 'Female', 'other' => 'Other']" />
                <x-field name="phone" label="Telephone number" :value="$patient?->phone" :required="true" maxlength="20" pattern="\+?[0-9][0-9\s\-]{6,18}" help="e.g. 0999 123 456 or +265 999 123 456" />
                <x-textarea name="address" label="Physical / residential address" :value="$patient?->address" :required="true" rows="2" maxlength="255" />
                <x-field name="next_of_kin_name" label="Next of kin – full name" :value="$patient?->next_of_kin_name" :required="true" minlength="3" maxlength="150" />
                <x-field name="next_of_kin_phone" label="Next of kin – telephone" :value="$patient?->next_of_kin_phone" :required="true" maxlength="20" pattern="\+?[0-9][0-9\s\-]{6,18}" />
                <x-select name="primary_doctor_id" label="Registered (primary) doctor" :required="true" :value="$patient?->primary_doctor_id"
                          :options="$doctors->mapWithKeys(fn ($d) => [$d->id => $d->name.' – '.$d->domain()->employmentLabel()])->all()" placeholder="Select doctor…" />

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $patient ? 'Save changes' : 'Register patient' }}</button>
                    <a href="{{ $patient ? route('patients.show', $patient) : route('patients.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
