@extends('layouts.app')
@section('title', $doctor->name)
@section('crumbs')<li class="breadcrumb-item"><a href="{{ route('doctors.index') }}">Doctors</a></li>@endsection
@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="page-title mb-1">{{ $doctor->name }}</h1>
            <span class="badge text-bg-{{ $doctor->isVisiting() ? 'warning' : 'primary' }}">{{ $doctor->domain()->employmentLabel() }}</span>
            <x-status-badge :status="$doctor->is_active ? 'active' : 'inactive'" />
        </div>
        @can('doctors.manage')
            <div class="d-flex gap-2">
                <a href="{{ route('doctors.edit', $doctor) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
                <form method="POST" action="{{ route('doctors.toggle', $doctor) }}" data-confirm="{{ $doctor->is_active ? 'Deactivate' : 'Activate' }} {{ $doctor->name }}? {{ $doctor->is_active ? 'They will no longer receive bookings or be able to sign in.' : '' }}">
                    @csrf <button class="btn {{ $doctor->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $doctor->is_active ? 'Deactivate' : 'Activate' }}</button></form>
            </div>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card"><div class="card-header">Profile</div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Doctor number</span><span>{{ $doctor->doctor_number }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Telephone</span><span>{{ $doctor->phone }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Consulting room</span><span>{{ $doctor->consulting_room }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Username</span><span>{{ $doctor->user->username }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Upcoming appointments</span><span>{{ $upcomingCount }}</span></li>
                    @if($doctor->isVisiting())
                        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Next visit</span><span>{{ $doctor->next_visit_date?->format('d M Y') ?? '—' }}</span></li>
                        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Visit duration</span><span>{{ $doctor->next_visit_duration_days ? $doctor->next_visit_duration_days.' day(s)' : '—' }}</span></li>
                    @endif
                    <li class="list-group-item"><span class="text-muted">{{ $doctor->domain()->availabilitySummary() }}</span></li>
                </ul></div>
        </div>
        <div class="col-lg-8">
            <div class="card mb-3"><div class="card-header">Weekly availability</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Day</th><th>Hours</th><th>Slot length</th>@can('doctors.manage')<th></th>@endcan</tr></thead>
                        <tbody>
                        @forelse($doctor->schedules as $s)
                            <tr><td>{{ $s->day_name }}</td><td>{{ $s->start_short }} – {{ $s->end_short }}</td><td>{{ $s->slot_minutes }} min</td>
                                @can('doctors.manage')
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('doctors.schedules.destroy', [$doctor, $s]) }}" data-confirm="Remove this availability block?">
                                            @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="empty-state"><i class="bi bi-clock"></i>No availability configured – this doctor cannot be booked yet.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @can('doctors.manage')
                <div class="card"><div class="card-header">Add availability</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('doctors.schedules.store', $doctor) }}" class="row g-3 align-items-end">
                            @csrf
                            <x-select name="day_of_week" label="Day" :required="true" col="col-md-3" :options="$days" placeholder="Day…" />
                            <x-field name="start_time" label="From" type="time" :required="true" col="col-md-2" value="08:00" />
                            <x-field name="end_time" label="To" type="time" :required="true" col="col-md-2" value="16:00" />
                            <x-field name="slot_minutes" label="Slot (min)" type="number" :required="true" col="col-md-2" value="30" min="10" max="120" />
                            <div class="col-md-3"><button class="btn btn-teal w-100"><i class="bi bi-plus-lg me-1"></i>Add</button></div>
                        </form>
                    </div></div>
            @endcan
        </div>
    </div>
@endsection
