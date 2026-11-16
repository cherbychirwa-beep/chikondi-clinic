@extends('layouts.app')
@section('title', 'Audit logs')
@section('content')
    <h1 class="page-title mb-3">Audit logs</h1>

    <form method="GET" class="card card-body mb-3 row g-2 align-items-end flex-row">
        <div class="col-md-3"><label class="form-label" for="user_id">User</label>
            <select name="user_id" id="user_id" class="form-select"><option value="">All users</option>
                @foreach($users as $u)<option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }} ({{ $u->username }})</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label" for="action">Action</label>
            <input list="actions" id="action" name="action" value="{{ request('action') }}" class="form-control" placeholder="e.g. payment">
            <datalist id="actions">@foreach($actions as $a)<option value="{{ $a }}">@endforeach</datalist></div>
        <div class="col-md-2"><label class="form-label" for="from">From</label><input type="date" id="from" name="from" value="{{ request('from') }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label" for="to">To</label><input type="date" id="to" name="to" value="{{ request('to') }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label" for="q">Description contains</label><input type="text" id="q" name="q" value="{{ request('q') }}" class="form-control"></div>
        @include('partials.filter-actions', ['clear' => route('audit.index')])
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Description</th><th>Record</th><th>IP</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap small">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                        <td>{{ $log->user?->username ?? 'system' }}</td>
                        <td><span class="badge text-bg-light border">{{ $log->action }}</span></td>
                        <td>{{ $log->description }}</td>
                        <td class="small text-muted">{{ $log->auditable_type }}@if($log->auditable_id) #{{ $log->auditable_id }}@endif</td>
                        <td class="small text-muted">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><i class="bi bi-shield-check"></i>No audit records match.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())<div class="card-footer">{{ $logs->links() }}</div>@endif
    </div>
@endsection
