@extends('layouts.app')
@section('title', 'Doctors')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title mb-0">Doctors</h1>
        <div class="d-flex gap-2">
            @can('reports.visiting')
                <a href="{{ route('reports.visiting') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-pdf me-1"></i>Visiting schedule (PDF)</a>
            @endcan
            @can('doctors.manage')
                <a href="{{ route('doctors.create') }}" class="btn btn-teal"><i class="bi bi-plus-lg me-1"></i>Register doctor</a>
            @endcan
        </div>
    </div>

    <form method="GET" class="card card-body mb-3 row g-2 align-items-end flex-row">
        <div class="col-md-4"><label class="form-label" for="q">Search</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name or doctor number"></div>
        <div class="col-md-2"><label class="form-label" for="type">Employment</label>
            <select name="type" id="type" class="form-select"><option value="">All</option>
                <option value="full_time" @selected(request('type') === 'full_time')>Full-time</option>
                <option value="visiting" @selected(request('type') === 'visiting')>Visiting</option></select></div>
        <div class="col-md-2"><label class="form-label" for="status">Status</label>
            <select name="status" id="status" class="form-select"><option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
        @include('partials.filter-actions', ['clear' => route('doctors.index')])
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>No.</th><th>Doctor</th><th>Employment</th><th>Room</th><th>Availability</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($doctors as $d)
                    <tr>
                        <td>{{ $d->doctor_number }}</td>
                        <td class="fw-semibold">{{ $d->name }}<div class="small text-muted fw-normal">{{ $d->phone }}</div></td>
                        <td><span class="badge text-bg-{{ $d->isVisiting() ? 'warning' : 'primary' }}">{{ $d->domain()->employmentLabel() }}</span></td>
                        <td>{{ $d->consulting_room }}</td>
                        <td class="small">{{ $d->domain()->availabilitySummary() }}</td>
                        <td><x-status-badge :status="$d->is_active ? 'active' : 'inactive'" /></td>
                        <td class="text-end"><a href="{{ route('doctors.show', $d) }}" class="btn btn-sm btn-outline-secondary">Profile &amp; schedule</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-person-badge"></i>No doctors found.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($doctors->hasPages())<div class="card-footer">{{ $doctors->links() }}</div>@endif
    </div>
@endsection
