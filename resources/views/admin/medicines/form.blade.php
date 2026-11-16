@extends('layouts.app')
@section('title', $medicine ? 'Edit medicine' : 'Add medicine')
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('medicines.index') }}">Medicines</a></li>@endsection
@section('content')
    <div class="card" style="max-width:760px">
        <div class="card-body p-4">
            <form method="POST" action="{{ $medicine ? route('medicines.update', $medicine) : route('medicines.store') }}" class="row g-3">
                @csrf
                @if($medicine) @method('PUT') @endif
                <x-field name="name" label="Medicine name" :value="$medicine?->name" :required="true" maxlength="150" />
                <x-field name="generic_name" label="Generic name" :value="$medicine?->generic_name" maxlength="150" />
                <x-field name="strength" label="Strength" :value="$medicine?->strength" maxlength="50" help="e.g. 500mg" />
                <x-field name="dosage_form" label="Dosage form" :value="$medicine?->dosage_form" maxlength="50" help="e.g. Tablet, Syrup, Capsule" />
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $medicine ? 'Save changes' : 'Add medicine' }}</button>
                    <a href="{{ route('medicines.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
