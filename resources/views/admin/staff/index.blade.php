@extends('layouts.app')
@section('title', 'Staff accounts')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="page-title mb-0">Staff accounts</h1>
        <a href="{{ route('staff.create') }}" class="btn btn-teal"><i class="bi bi-plus-lg me-1"></i>New staff account</a>
    </div>

    <form method="GET" class="card card-body mb-3 row g-2 align-items-end flex-row">
        <div class="col-md-4"><label class="form-label" for="q">Search</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, username or email"></div>
        <div class="col-md-2"><label class="form-label" for="role_id">Role</label>
            <select name="role_id" id="role_id" class="form-select"><option value="">All</option>
                @foreach($roles as $r)<option value="{{ $r->id }}" @selected(request('role_id') == $r->id)>{{ $r->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label" for="status">Status</label>
            <select name="status" id="status" class="form-select"><option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
        @include('partials.filter-actions', ['clear' => route('staff.index')])
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Last login</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse($users as $u)
                    <tr>
                        <td>{{ $u->name }}<div class="small text-muted">{{ $u->email }}</div></td>
                        <td>{{ $u->username }}@if($u->must_change_password) <span class="badge text-bg-light border">temp password</span>@endif</td>
                        <td>{{ $u->role->name }}</td>
                        <td><x-status-badge :status="$u->is_active ? 'active' : 'inactive'" /></td>
                        <td class="small text-muted">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="text-end">
                            @if($u->hasRole('doctor'))
                                <a href="{{ route('doctors.index', ['q' => $u->name]) }}" class="btn btn-sm btn-outline-secondary">Manage in Doctors</a>
                            @else
                                <a href="{{ route('staff.edit', $u) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endif
                            <form method="POST" action="{{ route('staff.reset', $u) }}" class="d-inline" data-confirm="Reset the password for {{ $u->username }}? A new temporary password will be generated.">
                                @csrf <button class="btn btn-sm btn-outline-warning">Reset password</button></form>
                            @if($u->id !== auth()->id())
                                <form method="POST" action="{{ route('staff.toggle', $u) }}" class="d-inline"
                                      data-confirm="{{ $u->is_active ? 'Deactivate' : 'Activate' }} the account {{ $u->username }}?">
                                    @csrf <button class="btn btn-sm {{ $u->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $u->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><i class="bi bi-people"></i>No staff accounts match your filters.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())<div class="card-footer">{{ $users->links() }}</div>@endif
    </div>
@endsection
