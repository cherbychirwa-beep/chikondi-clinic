@extends('pdf.layout')
@section('body')
    <h2>Visiting doctor schedule</h2>
    <table>
        <thead><tr><th>Doctor</th><th>Room</th><th>Next visit</th><th>Until</th><th>Duration</th><th>Weekly hours</th></tr></thead>
        <tbody>
        @forelse($doctors as $d)
            @php($v = $d->domain())
            <tr>
                <td>{{ $d->name }}<br><span class="muted">{{ $d->doctor_number }} · {{ $d->phone }}</span></td>
                <td>{{ $d->consulting_room }}</td>
                <td>{{ $v->visitStart()?->format('d M Y') ?? '—' }}</td>
                <td>{{ $v->visitEnd()?->format('d M Y') ?? '—' }}</td>
                <td>{{ $d->next_visit_duration_days ? $d->next_visit_duration_days.' day(s)' : '—' }}</td>
                <td>@forelse($d->schedules as $s){{ substr($s->day_name, 0, 3) }} {{ $s->start_short }}-{{ $s->end_short }}<br>@empty<span class="muted">None set</span>@endforelse</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">No active visiting doctors.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
