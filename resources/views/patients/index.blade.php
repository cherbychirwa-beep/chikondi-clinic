@extends('layouts.app')
@section('title', auth()->user()->hasRole('doctor') ? 'My patients' : 'Patients')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title mb-0">{{ auth()->user()->hasRole('doctor') ? 'My patients' : 'Patients' }}</h1>
        @can('patients.register')
            <a href="{{ route('patients.create') }}" class="btn btn-teal"><i class="bi bi-person-plus me-1"></i>Register patient</a>
        @endcan
    </div>

    <form method="GET" class="card card-body mb-3 row g-2 align-items-end flex-row">
        <div class="col-md-4"><label class="form-label" for="q">Search</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" class="form-control" placeholder="Patient number, name or phone"></div>
        <div class="col-md-2"><label class="form-label" for="dob">Date of birth</label>
            <input type="date" id="dob" name="dob" value="{{ request('dob') }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label" for="gender">Gender</label>
            <select name="gender" id="gender" class="form-select"><option value="">All</option>
                @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $k => $v)<option value="{{ $k }}" @selected(request('gender') === $k)>{{ $v }}</option>@endforeach</select></div>
        @include('partials.filter-actions', ['clear' => route('patients.index')])
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Patient no.</th><th>Name</th><th>Date of birth</th><th>Gender</th><th>Phone</th><th>Registered doctor</th><th></th></tr></thead>
                <tbody>
                @forelse($patients as $p)
                    <tr>
                        <td class="fw-semibold">{{ $p->patient_number }}</td>
                        <td>{{ $p->full_name }}</td>
                        <td>{{ $p->date_of_birth->format('d M Y') }} <span class="text-muted small">({{ $p->age }}y)</span></td>
                        <td>{{ ucfirst($p->gender) }}</td>
                        <td>{{ $p->phone }}</td>
                        <td>{{ $p->primaryDoctor?->name ?? '—' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('patients.show', $p) }}" class="btn btn-sm btn-outline-secondary">Profile</a>
                            @can('appointments.book')<a href="{{ route('appointments.book', ['patient_id' => $p->id]) }}" class="btn btn-sm btn-outline-teal">Book</a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-people"></i>No patients match your search.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($patients->hasPages())<div class="card-footer">{{ $patients->links() }}</div>@endif
    </div>
@endsection
