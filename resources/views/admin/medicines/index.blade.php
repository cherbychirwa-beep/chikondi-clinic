@extends('layouts.app')
@section('title', 'Medicine catalogue')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title mb-0">Medicine catalogue</h1>
        <a href="{{ route('medicines.create') }}" class="btn btn-teal"><i class="bi bi-plus-lg me-1"></i>Add medicine</a>
    </div>

    <form method="GET" class="card card-body mb-3 row g-2 align-items-end flex-row">
        <div class="col-md-5"><label class="form-label" for="q">Search</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" class="form-control" placeholder="Medicine or generic name"></div>
        <div class="col-md-2"><label class="form-label" for="status">Status</label>
            <select name="status" id="status" class="form-select"><option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
        @include('partials.filter-actions', ['clear' => route('medicines.index')])
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Name</th><th>Generic</th><th>Strength</th><th>Form</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse($medicines as $m)
                    <tr>
                        <td class="fw-semibold">{{ $m->name }}</td><td>{{ $m->generic_name ?? '—' }}</td><td>{{ $m->strength ?? '—' }}</td><td>{{ $m->dosage_form ?? '—' }}</td>
                        <td><x-status-badge :status="$m->is_active ? 'active' : 'inactive'" /></td>
                        <td class="text-end">
                            <a href="{{ route('medicines.edit', $m) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('medicines.toggle', $m) }}" class="d-inline" data-confirm="{{ $m->is_active ? 'Deactivate' : 'Activate' }} {{ $m->name }}?">
                                @csrf <button class="btn btn-sm {{ $m->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $m->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><i class="bi bi-capsule"></i>No medicines found.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($medicines->hasPages())<div class="card-footer">{{ $medicines->links() }}</div>@endif
    </div>
@endsection
